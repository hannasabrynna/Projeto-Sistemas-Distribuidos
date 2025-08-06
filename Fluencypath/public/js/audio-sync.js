document.addEventListener("DOMContentLoaded", function () {
    // Se já existir uma instância anterior, destrói
    if (window.waveSurfer && typeof window.waveSurfer.destroy === "function") {
        window.waveSurfer.destroy();
    }

    let waveSurfer;
    let sentenceTimestamps = [];
    const sentences = document.querySelectorAll(".sentence");
    const playButton = document.getElementById("playButton");
    const audioTimer = document.getElementById("audioTimer");

    function formatTime(seconds) {
        let minutes = Math.floor(seconds / 60);
        let secs = Math.floor(seconds % 60);
        return `${minutes.toString().padStart(2, "0")}:${secs.toString().padStart(2, "0")}`;
    }

    function initializeWavesurfer() {
        const audioPath = playButton.getAttribute("data-audio");
        if (!audioPath) {
            console.error("Erro: Caminho do áudio não encontrado.");
            return;
        }

        waveSurfer = WaveSurfer.create({
            container: "#waveform",
            waveColor: "gray",
            progressColor: "black",
            cursorColor: "black",
            height: 3,
        });

        waveSurfer.load(audioPath);

        waveSurfer.on("audioprocess", function () {
            const currentTime = waveSurfer.getCurrentTime();
            audioTimer.innerText = formatTime(currentTime);
            highlightCurrentSentence(currentTime);
        });

        waveSurfer.on("ready", function () {
            const totalDuration = waveSurfer.getDuration();
            calculateTimestamps(totalDuration);
            audioTimer.innerText = formatTime(0);
        });

        waveSurfer.on("finish", function () {
            audioTimer.innerText = formatTime(waveSurfer.getDuration());
            playButton.innerText = "▶️";
        });

        // Salva no escopo global para evitar duplicação
        window.waveSurfer = waveSurfer;
    }

    initializeWavesurfer();

    playButton.addEventListener("click", function () {
        if (!waveSurfer) return;

        if (waveSurfer.isPlaying()) {
            waveSurfer.pause();
            playButton.innerText = "▶️";
        } else {
            waveSurfer.play();
            playButton.innerText = "⏸️";
        }
    });

    function calculateTimestamps(totalDuration) {
        const numSentences = sentences.length;
        let totalWords = 0;
        const wordsPerSentence = [];

        sentences.forEach(sentence => {
            const words = sentence.textContent.trim().split(/\s+/).length;
            wordsPerSentence.push(words);
            totalWords += words;
        });

        let accumulatedTime = 0;
        sentenceTimestamps = [];

        wordsPerSentence.forEach((words, index) => {
            let sentenceDuration = (words / totalWords) * totalDuration;
            sentenceDuration += 0.5;
            sentenceTimestamps.push(accumulatedTime);
            accumulatedTime += sentenceDuration;
        });
    }

    function highlightCurrentSentence(currentTime) {
        sentences.forEach((sentence, index) => {
            const isCurrent =
                currentTime >= sentenceTimestamps[index] &&
                (index === sentenceTimestamps.length - 1 || currentTime < sentenceTimestamps[index + 1]);

            const portugueseSentence = document.getElementById(`sentence-pt-${index}`);

            if (isCurrent) {
                sentence.classList.add("highlight");
                if (portugueseSentence) portugueseSentence.classList.add("highlight");
            } else {
                sentence.classList.remove("highlight");
                if (portugueseSentence) portugueseSentence.classList.remove("highlight");
            }
        });
    }
});
