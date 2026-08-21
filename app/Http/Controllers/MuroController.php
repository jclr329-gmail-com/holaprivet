<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\WallOwnership;
use App\Models\WallWord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * El muro de palabras: el curso es gratis; quien quiere apoyarlo apadrina
 * una palabra durante un año (3 EUR la normal, 6 EUR la especial).
 *
 * Circuito: elegir palabra libre -> reservarla media hora -> Stripe
 * Checkout (la tarjeta jamas pasa por nuestra web) -> el webhook confirma
 * -> la palabra queda ocupada y la propiedad activa. La dedicatoria pasa
 * por moderacion antes de mostrarse.
 */
class MuroController extends Controller
{
    // --------------------------------------------------------------- muro

    public function muro()
    {
        $this->liberarReservasCaducadas();

        $palabras = WallWord::with(['propiedad' => fn ($q) => $q->with('usuario')])
            ->orderBy('id')
            ->get();

        return view('muro', [
            'palabras' => $palabras,
            'precios'  => config('muro.precios'),
        ]);
    }

    // ---------------------------------------------------------- apadrinar

    public function formulario(WallWord $palabra)
    {
        $this->liberarReservasCaducadas();

        if ($palabra->status !== 'libre') {
            return redirect()->route('muro')
                ->with('estado', 'Это слово уже занято — выберите другое.');
        }

        return view('muro-apadrinar', ['palabra' => $palabra]);
    }

    public function apadrinar(Request $peticion, WallWord $palabra)
    {
        $datos = $peticion->validate([
            'display_name' => ['required', 'string', 'max:60'],
            'dedication'   => ['nullable', 'string', 'max:100'],
        ]);

        // Reserva atomica: solo si sigue libre. Dos personas a la vez sobre
        // la misma palabra: gana una, la otra recibe el aviso.
        $reservada = WallWord::where('id', $palabra->id)
            ->where('status', 'libre')
            ->update([
                'status'         => 'reservada',
                'reserved_until' => now()->addMinutes(config('muro.reserva_min')),
            ]);

        if (! $reservada) {
            return redirect()->route('muro')
                ->with('estado', 'Кто-то только что занял это слово — выберите другое.');
        }

        $pedido = Order::create([
            'user_id'     => Auth::id(),
            'total_cents' => $palabra->price_cents,
            'currency'    => 'EUR',
            'status'      => 'pendiente',
        ]);
        OrderItem::create([
            'order_id'    => $pedido->id,
            'word_id'     => $palabra->id,
            'price_cents' => $palabra->price_cents,
        ]);
        $pago = Payment::create([
            'order_id'     => $pedido->id,
            'gateway'      => 'stripe',
            'amount_cents' => $palabra->price_cents,
            'status'       => 'pendiente',
        ]);

        // La sesion de Checkout: pagina de pago alojada por Stripe.
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $sesion = \Stripe\Checkout\Session::create([
            'mode'                => 'payment',
            'customer_email'      => Auth::user()->email,
            'client_reference_id' => (string) $pedido->id,
            'metadata'            => [
                'order_id'     => $pedido->id,
                'word_id'      => $palabra->id,
                'display_name' => $datos['display_name'],
                'dedication'   => $datos['dedication'] ?? '',
            ],
            'line_items' => [[
                'quantity'   => 1,
                'price_data' => [
                    'currency'     => config('muro.moneda'),
                    'unit_amount'  => $palabra->price_cents,
                    'product_data' => [
                        'name'        => 'Слово «' . $palabra->word . '» на стене holaprivet',
                        'description' => 'Годовой вклад в бесплатный курс испанского',
                    ],
                ],
            ]],
            'success_url' => route('muro.gracias') . '?sesion={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('muro') . '?cancelado=1',
        ]);

        $pago->update(['gateway_ref' => $sesion->id]);

        return redirect()->away($sesion->url);
    }

    // -------------------------------------------------------------- vuelta

    public function gracias(Request $peticion)
    {
        // El webhook es la autoridad; esto es la confirmacion inmediata al
        // volver de Stripe, para no hacer esperar a quien acaba de pagar.
        $idSesion = $peticion->query('sesion');

        if ($idSesion) {
            try {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                $sesion = \Stripe\Checkout\Session::retrieve($idSesion);
                if ($sesion->payment_status === 'paid') {
                    $this->activar($sesion);
                }
            } catch (\Throwable) {
                // el webhook lo resolvera en segundos
            }
        }

        return redirect()->route('muro')
            ->with('estado', '¡Gracias! Ваше слово уже на стене. Посвящение появится после короткой модерации.');
    }

    /** Idempotente: lo llaman el webhook y la vuelta, gana el primero. */
    public function activar(\Stripe\Checkout\Session $sesion): void
    {
        DB::transaction(function () use ($sesion) {
            $pedido = Order::lockForUpdate()->find($sesion->metadata->order_id ?? 0);
            if (! $pedido || $pedido->status === 'pagado') {
                return;
            }

            $pedido->update(['status' => 'pagado']);

            Payment::where('order_id', $pedido->id)->update([
                'status'      => 'completado',
                'gateway_ref' => $sesion->id,
                'paid_at'     => now(),
                'raw'         => json_encode(['payment_intent' => $sesion->payment_intent]),
            ]);

            $palabra = WallWord::lockForUpdate()->find($sesion->metadata->word_id ?? 0);
            if (! $palabra) {
                return;
            }

            $palabra->update(['status' => 'ocupada', 'reserved_until' => null]);

            WallOwnership::create([
                'word_id'      => $palabra->id,
                'user_id'      => $pedido->user_id,
                'display_name' => mb_substr($sesion->metadata->display_name ?? '', 0, 60) ?: '—',
                'dedication'   => mb_substr($sesion->metadata->dedication ?? '', 0, 100) ?: null,
                'moderation'   => 'pendiente',
                'starts_at'    => now(),
                'expires_at'   => now()->addDays(config('muro.duracion_dias')),
                'grace_until'  => now()->addDays(config('muro.duracion_dias') + config('muro.gracia_dias')),
                'status'       => 'activa',
            ]);
        });
    }

    // ------------------------------------------------------------ limpieza

    protected function liberarReservasCaducadas(): void
    {
        $caducadas = WallWord::where('status', 'reservada')
            ->where('reserved_until', '<', now())
            ->pluck('id');

        if ($caducadas->isEmpty()) {
            return;
        }

        WallWord::whereIn('id', $caducadas)
            ->update(['status' => 'libre', 'reserved_until' => null]);

        // Sus pedidos pendientes se cancelan; los pagados no se tocan.
        Order::where('status', 'pendiente')
            ->whereHas('items', fn ($q) => $q->whereIn('word_id', $caducadas))
            ->update(['status' => 'cancelado']);
    }
}
