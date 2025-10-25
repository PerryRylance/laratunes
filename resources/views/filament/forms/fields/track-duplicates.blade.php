<section>
    <p>
        This track appears to be {{ $relation === 'originals' ? 'a duplicate' : 'an original' }} of the following:
    </p>
    <ul>
        @foreach ($getRecord()->{$relation} as $other)
            <li>
                <a href="/admin/tracks/{{ $other->id }}">{{ $other->caption }}</a>
            </li>
        @endforeach
    </ul>
    <!-- TODO: Would be nice to be able to dismiss duplicates -->
</section>
