<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * El webhook de Stripe: la autoridad sobre que se ha pagado.
 *
 * Llega firmado; sin firma valida no se procesa nada. Exento de CSRF en
 * bootstrap/app.php (lo firma Stripe, no un navegador con sesion).
 */
class StripeWebhookController extends Controller
{
    public function recibir(Request $peticion, MuroController $muro)
    {
        $secreto = config('services.stripe.webhook_secret');

        try {
            $evento = \Stripe\Webhook::constructEvent(
                $peticion->getContent(),
                $peticion->header('Stripe-Signature', ''),
                $secreto
            );
        } catch (\Throwable) {
            return response('firma invalida', 400);
        }

        if ($evento->type === 'checkout.session.completed') {
            $sesion = $evento->data->object;
            if (($sesion->payment_status ?? '') === 'paid') {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
                $muro->activar($sesion);
            }
        }

        return response('ok');
    }
}
