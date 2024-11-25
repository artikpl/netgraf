<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            @if (isset($pet))
                {{ __('Edycja peta ') }} {{ $pet->id }}
            @else
                {{ __('Tworzenie nowego peta') }}
            @endif

        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form name="pet">
                        @csrf
                        @if (isset($pet))
                            <input type="hidden" name="id" value="{{ $pet->id }}">
                        @endif
                        <fieldset>
                            <legend>Nazwa</legend>
                            <input type="text" name="name" value="{{ $pet->name ?? '' }}">
                        </fieldset>
                        <fieldset>
                            <legend>Status</legend>
                            <select name="status">
                                <option></option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->code }}"@if (isset($pet) && $pet->status->code===$status->code) selected @endif>{{ $status->name }}</option>
                                @endforeach
                            </select>
                        </fieldset>
                        <fieldset>
                            <legend>Kategoria</legend>
                            ID <input type="number" name="category.id" value="{{ $pet->category->id ?? '' }}"> Wartość <input type="text" value="{{ $pet->category->name ?? '' }}" name="category.name">
                        </fieldset>
                        <fieldset>
                            <legend>TAGI</legend>
                            <div class="tags-node">
                                @foreach ($pet->tags ?? [] as $tagId => $tag)
                                    <p>#{{ $tag->id }}, {{ $tag->name }}
                                        <input type="hidden" name="tags[{{ $tagId }}][id]" value="{{ $tag->id }}">
                                        <input type="hidden" name="tags[{{ $tagId }}][name]" value="{{ $tag->name }}">
                                        <button type="button" name="remove-tag">Usuń</button></p>
                                @endforeach
                            </div>
                            <fieldset>
                                <legend>Dodawanie nowego</legend>
                                ID <input type="number" name="tag.id"> Nazwa <input type="text" name="tag.name"> <button type="button" name="add.tag">Dodaj tag</button>
                            </fieldset>
                        </fieldset>
                        <fieldset>
                            <legend>Zdjęcia</legend>
                            <div class="photos-node">
                                @foreach ($pet->photoUrls ?? [] as $pos => $url)
                                    <p>{{ $url }} <button type="button" name="remove-photo">Usuń</button>
                                        <input type="hidden" name="photos[{{ $pos }}]" value="{{ $url }}">
                                    </p>
                                @endforeach
                            </div>
                            <fieldset>
                                <legend>Dodawanie nowego</legend>
                                URL <input type="text" name="photo.url"> <button type="button" name="add.photo">Dodaj zdjęcie</button>
                            </fieldset>
                        </fieldset>
                        <button type="button" name="save.pet">
                           Zapisz zwierzaka
                        </button>
                        @if (isset($pet->id))
                            <button type="button" name="remove-pet">
                                USUŃ
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
