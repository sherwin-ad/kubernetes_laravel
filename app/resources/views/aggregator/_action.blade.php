<div class="dropdown">
    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        Action
    </button>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="{{ route('aggregator.edit', $aggregator->id) }}">Edit</a></li>
        {{-- make delete button --}}
        <li>
            <form action="{{ route('aggregator.destroy', $aggregator->id) }}" method="post">
                @csrf
                @method('DELETE')
                <button type="submit" class="dropdown-item">Delete</button>
            </form>
        </li>
    </ul>
</div>
