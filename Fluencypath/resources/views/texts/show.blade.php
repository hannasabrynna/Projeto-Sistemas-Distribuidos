@extends('layouts.app')

<!-- CSS - sincronização de audio e detalhe das palavras -->
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<link />

@section('content')

<section class="container max-w-7xl mx-auto px-4 sm:px-10 lg:px-10">
    <div class="card" style="padding: 15px; margin: 15px;">
        <nav aria-label="breadcrumb" class="pb-10">
            <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                <li>
                    <div class="flex items-center">

                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">

                    </div>
                </li>
            </ol>
        </nav>

        <article>
            <header class="grid grid-cols-2 pt-10 pb-2">
                <h3 class="font-primary font-medium text-2xl text-neutral-600">{{ $texts->title }}</h3>

                <div class="inline-flex items-end justify-end gap-2">
                    <div class="w-[60px] h-[40px] flex items-center justify-center bg-primary-200 border border-neutral-100 rounded">
                        <button
                            class="favorite-btn flex items-center space-x-2"
                            data-text-id="{{ $texts->id }}"
                            data-favorited="{{ Auth::user()->favorites()->where('text_id', $texts->id)->exists() ? 'true' : 'false' }}">
                            <span class="favorite-icon text-xl">
                                {{ Auth::user()->favorites()->where('text_id', $texts->id)->exists() ? '❤️' : '🤍' }}
                            </span>
                            <span class="favorites-count text-gray-600 text-lg">
                                {{ $texts->favorites_count }}
                            </span>
                        </button>
                    </div>

                    <x-tertiary-button>
                        <x-heroicon-s-plus class="w-6 h-6 text-primary-300" />
                        <a href="{{ route('texts.create') }}" class="font-primary text-primary-300">Adicionar texto</a>
                    </x-tertiary-button>


                    @if(( Auth::user() && Auth::user()->is_admin == 'y') || (Auth::user() && Auth::user()->id == $texts->idUser))

                    <x-dropdown-actions align="left">
                        <x-slot name="trigger">
                            <button class="h-[40px] flex items-center justify-center bg-primary-700 font-primary font-500 border border-transparent rounded-md text-primary-300  text-center text-sm tracking-widest hover:bg-primary-400 focus:bg-primary-400 active:bg-primary-900 focus:outline-none transition ease-in-out duration-150 shadow-lg  px-2 py-2">
                                <x-heroicon-s-ellipsis-horizontal class="w-6 h-6 text-primary-300" />
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('texts.edit', $texts->id)" class="inline-flex items-center justify-center font-primary font-medium  text-neutral-500 gap-2"><x-heroicon-o-pencil-square class="w-5 h-5 text-neutral-500" /> Editar</x-dropdown-link>
                            <form action="{{ route('texts.destroy', $texts->id) }}" method="POST" onclick="return confirm('Deseja excluir este texto?')" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex w-full items-center justify-center px-4 py-2 font-primary font-medium text-start text-sm leading-5 text-neutral-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out gap-2 ">
                                    <x-heroicon-o-trash class="w-5 h-5 text-neutral-500" /> Excluir
                                </button>
                            </form>
                        </x-slot>
                    </x-dropdown-actions>
                    @endif
                </div>
            </header>

            <section class="pb-2">
                <!-- TAGS -->
                <ul>
                    @php
                        $tags = explode(',', $texts->tag); // divide a string em um array
                    @endphp

                    @foreach ($tags as $tag)
                        <li class="inline-block bg-neutral-100 font-secondary font-semibold text-sm text-primary-900 py-1 px-3 mt-4 mb-10 rounded-full">
                            {{ trim($tag) }}
                        </li>
                    @endforeach
                </ul>
            </section>


            <article class="w-[60%] py-4">
                <!-- AUDIO -->
                <figure class="w-[100%] h-[40px] flex items-center bg-primary-200 border rounded-full p-4 my-4 gap-2">
                    <button id="playButton" data-audio="{{ Storage::url($texts->audio->file_path) }}">▶️</button>
                    <span id="audioTimer" class="font-secondary text-neutral-600 text-base">00:00</span>
                    <div id="waveform" class="w-[80%]"></div>
                </figure>
                <!-- TEXTO -->
                <p id="text-content">
                    @php
                        // Divide em frases para sincronização
                        $sentences = preg_split('/(?<=[.!?])\s+/', $texts->content, -1, PREG_SPLIT_NO_EMPTY);
                    @endphp


                    <p id="text-content" class=" bg-primary-200 border rounded-md text-neutral-600 text-justify p-8">
                    @foreach ($sentences as $index => $sentence)
                        <span class="sentence" id="sentence-{{ $index }}" data-index="{{ $index }}">
                            {{ $sentence }}
                        </span>
                    @endforeach
                </p>

                    </p>
            </article>
        </article>
    </div>
    </div>

    <!-- Importa o Wavesurfer -->
    <script src="https://unpkg.com/wavesurfer.js"></script>
    <!-- Importa o script para a sincronização -->
    <script src="{{ asset('/js/audio-sync.js') }}"></script>
    <!-- Importa o script do card das palras -->
    <script src="{{ asset('/js/word-tooltip.js') }}"></script>
    <!-- Importa o script do card das palras -->
    <script src="{{ asset('/js/btn-favorite-tooltip.js') }}"></script>

    <div id="tooltip" class="hidden absolute bg-white p-3 shadow-md border rounded-md"></div>
    @endsection

   <script>
document.addEventListener("DOMContentLoaded", async () => {
    const audio = new Audio("{{ Storage::url($texts->audio->file_path) }}");
    const playButton = document.getElementById("playButton");
    const timer = document.getElementById("audioTimer");

    const response = await fetch("{{ Storage::url($texts->audio->speech_marks_path) }}");
    const lines = (await response.text()).trim().split("\n");
    const marks = lines.map(line => JSON.parse(line)).filter(m => m.type === "sentence");

audio.addEventListener("timeupdate", () => {
    const currentTime = audio.currentTime;

    for (let i = 0; i < marks.length; i++) {
        const start = marks[i].time / 1000;
        const end = marks[i + 1] ? marks[i + 1].time / 1000 : audio.duration;

        const el = document.querySelector(`#sentence-${i}`);
        if (el) {
            if (currentTime >= start && currentTime < end) {
                el.classList.add("bg-yellow-200");
            } else {
                el.classList.remove("bg-yellow-200");
            }
        }
    }
});

    playButton.addEventListener("click", () => {
        if (audio.paused) {
            audio.play();
        } else {
            audio.pause();
        }
    });
});
</script>
