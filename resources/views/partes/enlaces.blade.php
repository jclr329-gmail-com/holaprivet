<ul class="enlaces">
  @foreach ($pieza->links as $e)
    <li>
      @if ($e->to_piece_id)
        <a href="{{ route('pieza', \App\Models\Piece::find($e->to_piece_id)->slug) }}">{{ $e->label }}</a>
      @else
        <span class="ru">{{ $e->label }}</span>
      @endif
    </li>
  @endforeach
</ul>
