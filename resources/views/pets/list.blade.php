<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pets') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <h2 class="p-2">{{ __("Lista zwierzaków") }}</h2>
                @if (isset($error))
                    <p class="pet-error">{{ $error }}</p>
                @endif

                @if (isset($confirmation))
                    <p class="pet-success">{{ $confirmation }}</p>
                @endif

                <div class="p-2 text-gray-900">
                    Jeżeli nie znalazłeś swojego peta <a href="{{ route('pets.empty') }}" class="pet-create-link">dodaj nowego</a>
                </div>
                <div class="p-2 text-gray-900">

                    @foreach ($statuses as $key => $status)
                        <span class="pet-category">
                        @if ($status->active??0 === 1)
                            <strong>{{ $status->name }}</strong>
                        @else
                            <a href="{{ route('pets.list',['status' => $status->code ]) }}">{{ $status->name }}</a>
                        @endif
                        </span>
                    @endforeach

                    <table class="pets-list">
                        <thead>
                            <th>ID</th>
                            <th>Nazwa</th>
                            <th>Kategoria</th>
                            <th>Tagi</th>
                        </thead>
                        <tbody>
                    @foreach($pets as $pet)
                        <tr>
                            <td><a href="{{ route('pets.details',['id' => $pet->id ]) }}">{{ $pet->id }}</td>
                            <td><a href="{{ route('pets.details',['id' => $pet->id ]) }}">{{ $pet->name }}</td>
                            <td>@if (isset($pet->category->id))
                                    {{ $pet->category->name }}
                                @endif</td>
                            <td>@if (isset($pet->tags) && is_array($pet->tags))
                                    @foreach($pet->tags as $tag)
                                        {{ $tag->name }}
                                    @endforeach
                                @endif</td>
                    @endforeach
                    </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
