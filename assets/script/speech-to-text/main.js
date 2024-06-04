let audioContext,
    audioInput = null,
    realAudioInput = null,
    inputPoint = null,
    audioRecorder = null,
    btSearchRAF = null,
    analyserContext = null,
    canvasWidth,
    canvasHeight,
    recIndex = 0,
    recording = false,
    btSearchSilenceTimer = false;

const btSearchGotBuffers = buffers => {
    /*
    NOT IN DESIGN
    var canvas = document.getElementById( "wavedisplay" );
    btSearchDrawBuffer( canvas.width, canvas.height, canvas.getContext('2d'), buffers[0] );
    */

    // the ONLY time btSearchGotBuffers is called is right after a new recording is completed -
    // so here's where we should set up the download.
    audioRecorder.exportWAV(btSearchDoneEncoding);
};

const btSearchDoneEncoding = blob => {
    Recorder.setupDownload(blob, 'intreb-bt-ai-search-audio-recording-' + (recIndex < 10 ? '0' : '') + recIndex + '.wav');
    recIndex++;
};

const btSearchDrawBuffer = (width, height, context, data) => {
    var step = Math.ceil( data.length / width );
    var amp = height / 2;
    context.fillStyle = "silver";
    context.clearRect(0,0,width,height);
    for(var i=0; i < width; i++){
        var min = 1.0;
        var max = -1.0;
        for (j=0; j<step; j++) {
            var datum = data[(i*step)+j];
            if (datum < min)
                min = datum;
            if (datum > max)
                max = datum;
        }
        context.fillRect(i,(1+min)*amp,1,Math.max(1,(max-min)*amp));
    }
};

const btSearchToggleRecording = () => {
    if (recording) {
        // stop
        audioRecorder.stop();
        audioRecorder.getBuffers(btSearchGotBuffers);
        btSearchCancelAnalyserUpdates();
    } else {
        // start
        if (!audioRecorder) {
            return;
        }
        audioRecorder.clear();
        audioRecorder.record();
    }

    recording = !recording;
};

const btSearchConvertToMono = input => {
    let splitter = audioContext.createChannelSplitter(2),
        merger = audioContext.createChannelMerger(2);

    input.connect(splitter);
    splitter.connect(merger, 0, 0);
    splitter.connect(merger, 0, 1);
    return merger;
};

const btSearchCancelAnalyserUpdates = () => {
    window.cancelAnimationFrame(btSearchRAF);
    btSearchRAF = null;
};

const btSearchUpdateAnalysers = time => {
    const freqByteData = new Uint8Array(analyserNode.frequencyBinCount);

    analyserNode.getByteFrequencyData(freqByteData);

    // detecting silence
    let silence = true;
    freqByteData.forEach(data => {
        if (data > 0) {
            silence = false;
        }
    });

    if (silence) {
        if (!btSearchSilenceTimer) {
            btSearchSilenceTimer = setTimeout(() => {
                btSearchToggleRecording();
                btSearchStopRecording();
            }, 2000);
        }
    } else {
        clearTimeout(btSearchSilenceTimer);
        btSearchSilenceTimer = false;
    }

    document.querySelectorAll('[data-recording-audio-waves]').forEach(waves => {
        waves.querySelectorAll('[data-recording-audio-wave]').forEach((wave, i) => {
            gsap.set(wave, {
                height: gsap.utils.mapRange(0, 255, 3, 24, freqByteData[i])
            })
        });
    });

    /*
    // Draw rectangle for each frequency bin.
    for (var i = 0; i < numBars; ++i) {
        var magnitude = 0;
        var offset = Math.floor( i * multiplier );
        // gotta sum/average the block, or we miss narrow-bandwidth spikes
        for (var j = 0; j< multiplier; j++)
            magnitude += freqByteData[offset + j];
        magnitude = magnitude / multiplier;
        var magnitude2 = freqByteData[i * multiplier];
        analyserContext.fillStyle = "hsl( " + Math.round((i*360)/numBars) + ", 100%, 50%)";
        analyserContext.fillRect(i * SPACING, canvasHeight, BAR_WIDTH, -magnitude);
    }
    */

    btSearchRAF = window.requestAnimationFrame(btSearchUpdateAnalysers);
};

const btSearchToggleMono = () => {
    if (audioInput != realAudioInput) {
        audioInput.disconnect();
        realAudioInput.disconnect();
        audioInput = realAudioInput;
    } else {
        realAudioInput.disconnect();
        audioInput = btSearchConvertToMono(realAudioInput);
    }

    audioInput.connect(inputPoint);
};

function gotStream(stream) {
    inputPoint = audioContext.createGain();

    // Create an AudioNode from the stream.
    realAudioInput = audioContext.createMediaStreamSource(stream);
    audioInput = realAudioInput;
    audioInput.connect(inputPoint);

    // audioInput = btSearchConvertToMono( input );

    analyserNode = audioContext.createAnalyser();
    //analyserNode.fftSize = 2048;
    analyserNode.fftSize = 32; // L-AM FACUT MAI MIC PENTRU CA NU ARE SENS ATATA PROCESARE DOAR PENTRU 8 BENZI
    inputPoint.connect(analyserNode);

    audioRecorder = new Recorder(inputPoint);

    zeroGain = audioContext.createGain();
    zeroGain.gain.value = 0.0;
    inputPoint.connect(zeroGain);
    zeroGain.connect(audioContext.destination);

    btSearchUpdateAnalysers();
}

const btSearchInitAudio = () => {
    window.AudioContext = window.AudioContext || window.webkitAudioContext;
    audioContext = new AudioContext();

    if (!navigator.cancelAnimationFrame) {
        navigator.cancelAnimationFrame = navigator.webkitCancelAnimationFrame || navigator.mozCancelAnimationFrame;
    }

    if (!navigator.requestAnimationFrame) {
        navigator.requestAnimationFrame = navigator.webkitRequestAnimationFrame || navigator.mozRequestAnimationFrame;
    }

    navigator.mediaDevices.getUserMedia({
        audio: true,
    }).then(stream => {
        gotStream(stream);
        btSearchToggleRecording();
    }).catch(error => {
        document.querySelectorAll('[data-record-audio], [data-recording-audio]').forEach(element => {
            element.remove();
        });
        console.log(error);
    });
};

document.addEventListener('click', event => {
    document.querySelectorAll('[data-record-audio]').forEach(element => {
        if (element.contains(event.target)) {
            btSearchInitAudio();
        }
    });

    document.querySelectorAll('[data-recording-audio]').forEach(element => {
        if (element.contains(event.target)) {
            btSearchToggleRecording();
        }
    });
});
