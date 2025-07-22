<div class="dropdown">
    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        Action
    </button>
    <ul class="dropdown-menu">

        @if ($merchant->aggregator_id)
            <li>
                <form action="{{ route('merchant.unassign', $merchant->id) }}" method="post">
                    @csrf
                    <button type="submit" class="dropdown-item unassign">Unassign from Aggregator</button>
                </form>
            </li>
        @else
            <li>
                <a class="dropdown-item assign" data-id="{{ $merchant->id }}" href="#">Assign to Aggregator</a>
            </li>
        @endif

        <li><a class="dropdown-item" href="{{ route('merchant.edit', $merchant->id) }}">Edit</a></li>
        <li>
            <form action="{{ route('merchant.destroy', $merchant->id) }}" method="post">
                @csrf
                @method('DELETE')
                <button type="submit" class="dropdown-item">Delete</button>
            </form>
        </li>
    </ul>
</div>
