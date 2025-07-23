@extends('layouts.app')

@section('content')
<section class="h-[300px] bg-neutral-200 mb-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-10 lg:px-10">

    </div>
</section>

<section class="max-w-7xl mx-auto px-4 sm:px-10 lg:px-10">
    <header class="flex justify-between">
        <h1 class="font-primary font-medium text-2xl text-primary-1000 py-8">
            {{ __('Textos') }}
        </h1>

        <div class="flex items-center justify-end gap-4">
            <x-dropdown-texts align="left">
                <x-slot name="trigger">
                    <button class="bg-white border rounded-lg flex items-center justify-between w-40 shadow px-4 py-2">
                        Textos
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('texts.index')">Textos</x-dropdown-link>
                    <x-dropdown-link :href="route('flashcards.index')">Flashcards</x-dropdown-link>
                    <x-dropdown-link :href="route('texts.index')">Meus textos</x-dropdown-link>
                    <x-dropdown-link :href="route('favorites.index')">Meus favoritos</x-dropdown-link>
                </x-slot>
            </x-dropdown-texts>

            <x-tertiary-button>
                <x-heroicon-s-plus class="w-6 h-6 text-primary-300" />
                <a href="{{ route('texts.create') }}" class="font-primary text-primary-300">Adicionar texto</a>
            </x-tertiary-button>
        </div>
    </header>

    <section class="grid grid-cols-1 grid-rows-12 sm:grid-cols-2 sm:grid-rows-6 lg:grid-cols-3 lg:grid-rows-4 gap-4 my-10">
        @php
        $user = Auth::user();
        $favoriteTexts = $user->favorites()->with('favorites')->get();
        @endphp
        @foreach ($texts as $text)
        <article class="w-full max-w-[340px] h-[260px] relative bg-primary-100 rounded-lg p-4 shadow-md my-4">
            <header class="flex items-center justify-between my-2">
                <a href="{{ route('texts.show', $text->id) }}" class="font-primary font-medium text-base text-primary-1000 truncate">
                    {{ $text->title }}
                </a>

                <div class="w-[60px] h-[30px] flex items-center justify-center bg-primary-200 border border-neutral-100 rounded">
                    <button
                        class="favorite-btn flex items-center space-x-2"
                        data-text-id="{{ $text->id }}"
                        data-favorited="{{ Auth::user()->favorites()->where('text_id', $text->id)->exists() ? 'true' : 'false' }}">
                        <span class="favorite-icon text-xl">
                            {{ Auth::user()->favorites()->where('text_id', $text->id)->exists() ? '❤️' : '🤍' }}
                        </span>
                        <span class="favorites-count text-gray-600 text-lg">
                            {{ $text->favorites_count }}
                        </span>
                    </button>
                </div>
            </header>

            <div class="grid grid-cols-2">
                <p class="text-sm text-gray-500 truncate">{{ $text->user->name }}</p>
                <p class="text-sm text-gray-500">- {{ $text->created_at->translatedFormat('M, j \d\e Y') }}</p>
            </div>

            <ul>
                @php
                $tags = explode(',', $text->tag);
                @endphp

                @foreach (array_slice($tags, 0, 5) as $tag)
                    <li class="inline-block bg-neutral-100 font-secondary font-semibold text-sm text-primary-900 py-1 px-3 mt-4 mb-2 rounded-full">
                        {{ trim($tag) }}
                    </li>
                @endforeach

            </ul>
            <p class="mt-2 text-sm text-gray-700 text-justify">{{ Str::limit($text->content, 160, '...') }}</p> <!--Limita até 25 caracteres do texto -->
            @if ($text->audio_path)
            <audio controls>
                <source src="{{ asset('storage/' . $text->audio_path) }}" type="audio/mpeg">
            </audio>
            @endif
        </article>
        @endforeach
    </section>

    <section class="p-10">
        <!-- Paginação que o laravel gera pronto automaticamente -->
        <div class="mt-4 flex justify-center">
            <div class="d-flex justify-content-center">
                {{ $texts->links('') }}
            </div>
        </div>
    </section>
</section>
@endsection