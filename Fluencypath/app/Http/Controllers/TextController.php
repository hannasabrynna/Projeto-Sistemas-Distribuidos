<?php

namespace App\Http\Controllers;

use App\Services\AmazonTranslateService;
use App\Services\AmazonPollyService;
use App\Models\Text;
use App\Models\Audio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TextController extends Controller
{
    public function index(Request $request)
    {
        $texts = Text::with('audio')->orderBy('created_at', 'desc')->get();
        // $texts = Text::all();
        $searchbar = $request->input('searchbar');

        $query = Text::with('audio');

        if ($request->has('searchbar')) {
            $query->where(function ($q) use ($searchbar) {
                $q->where('title', 'like', "%{$searchbar}%")
                    ->orWhere('content', 'like', "%{$searchbar}%")
                    ->orWhere('tag', 'like', "%{$searchbar}%");
            });
        }

        $texts = $query->get();

        $texts = $query->paginate(12);
        return view('texts.index', compact('texts'));
    }

public function store(Request $request, AmazonTranslateService $translateService)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'tag' => 'required|string|max:255',
        'audio' => 'nullable|file|mimes:mp3,wav,ogg|max:409600',
    ]);

    // Criar o texto
    $text = Text::create([
        'title' => $request->input('title'),
        'content' => $request->input('content'),
        'tag' => $request->input('tag'),
        'idUser' => auth()->id(),
    ]);

    // Tradução do texto para português (Amazon Translate)
    try {
        $translated = $translateService->translateText($text->content);
        $text->translated_content = $translated;
        $text->save();
    } catch (\Exception $e) {
        \Log::error('Erro ao traduzir o texto: ' . $e->getMessage());
    }

     //Gera automaticamente  via Polly
        $pollyService = new AmazonPollyService();
        $filePath = $pollyService->synthesizeSpeech($request->input('content'));
        $speechMarksPath = $pollyService->generateSpeechMarks($request->input('content'));

        Audio::create([
            'idText' => $text->id,
            'file_path' => $filePath,
            'speech_marks_path' => $speechMarksPath,
            'title' => $text->title,
        ]);


    return redirect()->route('texts.index')->with('success', 'Texto, áudio e tradução adicionados com sucesso!');
}


    public function create()
    {
        return view('texts.create');
    }

    public function edit($id)
    {
        $text = Text::findOrFail($id);
        return view('texts.edit', compact('text'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'tag' => 'required|string|max:255',
        ]);

        $text = Text::findOrFail($id);
        $text->update([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'tag' => $request->input('tag'),
        ]);

        return redirect()->route('texts.index')->with('success', 'Texto e  áudio atualizados com sucesso!');
    }

    public function show($id, AmazonTranslateService $translateService)
{
    $texts = Text::with('audio')->findOrFail($id);

    // Se não tiver tradução salva no banco
    if (!$texts->translated_content) {
        $translated = $translateService->translateText($texts->content);

        $texts->translated_content = $translated;
        $texts->save();

    }

    return view('texts.show', [
        'texts' => $texts,
        'translatedText' => $texts->translated_content
    ]);
}

    public function destroy($id)
    {
        $text = Text::with('audio')->findOrFail($id);

        if ($text->audio) {
            Storage::disk('s3')->delete($text->audio->file_path);
            $text->audio->delete();
        }

        $text->delete();

        return redirect()->route('texts.index')->with('success', 'Texto e áudio excluídos com sucesso!');
    }
}
