let abaAtiva = 'desenhar';
let cameraStream = null;

const canvas = document.getElementById('signatureCanvas');
const ctx = canvas.getContext('2d');
const canvasTexto = document.getElementById('canvasTexto');
const ctxTexto = canvasTexto ? canvasTexto.getContext('2d') : null;
const canvasUpload = document.getElementById('canvasUpload');
const ctxUpload = canvasUpload ? canvasUpload.getContext('2d') : null;

ctx.lineWidth = 2;
ctx.lineCap = 'round';
ctx.strokeStyle = '#000';
let drawing = false;

function userData(name) {
    const element = document.getElementById('user-data');
    return element ? (element.getAttribute(`data-${name}`) || '').trim() : '';
}

function registrarEvento(eventName, detail) {
    const uuid = userData('uuid');
    if (!uuid) return;
    const body = new URLSearchParams({event: eventName, uuid: uuid, detail: detail || ''});
    if (navigator.sendBeacon) {
        navigator.sendBeacon('registrar_evento.php', body);
        return;
    }
    fetch('registrar_evento.php', {method: 'POST', body: body, keepalive: true}).catch(() => {});
}

function getCoordinates(event) {
    const rect = canvas.getBoundingClientRect();
    const point = event.touches && event.touches.length ? event.touches[0] : event;
    return {
        x: (point.clientX - rect.left) * (canvas.width / rect.width),
        y: (point.clientY - rect.top) * (canvas.height / rect.height),
    };
}

function startDrawing(event) {
    drawing = true;
    const point = getCoordinates(event);
    ctx.beginPath();
    ctx.moveTo(point.x, point.y);
    event.preventDefault();
}

function draw(event) {
    if (!drawing) return;
    const point = getCoordinates(event);
    ctx.lineTo(point.x, point.y);
    ctx.stroke();
    event.preventDefault();
}

function stopDrawing() {
    drawing = false;
    ctx.beginPath();
}

canvas.addEventListener('mousedown', startDrawing);
canvas.addEventListener('mousemove', draw);
canvas.addEventListener('mouseup', stopDrawing);
canvas.addEventListener('mouseout', stopDrawing);
canvas.addEventListener('touchstart', startDrawing, {passive: false});
canvas.addEventListener('touchmove', draw, {passive: false});
canvas.addEventListener('touchend', stopDrawing, {passive: false});

function abrirModal() {
    document.getElementById('modalAssinatura').style.display = 'block';
}

function fecharModal() {
    document.getElementById('modalAssinatura').style.display = 'none';
}

window.addEventListener('click', function (event) {
    const modal = document.getElementById('modalAssinatura');
    if (event.target === modal) modal.style.display = 'none';
});

function mudarAba(aba, button) {
    abaAtiva = aba;
    document.querySelectorAll('.tab-btn').forEach((item) => item.classList.remove('active'));
    if (button) button.classList.add('active');
    document.querySelectorAll('.aba-content').forEach((content) => { content.style.display = 'none'; });
    const content = document.getElementById(`aba-${aba}`);
    if (content) content.style.display = 'block';
}

function atualizarPreviewTexto() {
    const texto = document.getElementById('textoAssinatura').value;
    const fonte = document.getElementById('fonteAssinatura').value;
    if (!texto || !ctxTexto) return;
    ctxTexto.clearRect(0, 0, canvasTexto.width, canvasTexto.height);
    ctxTexto.font = `48px ${fonte}`;
    ctxTexto.fillStyle = '#000';
    ctxTexto.textAlign = 'center';
    ctxTexto.textBaseline = 'middle';
    ctxTexto.fillText(texto, canvasTexto.width / 2, canvasTexto.height / 2);
}

document.getElementById('textoAssinatura').addEventListener('input', atualizarPreviewTexto);

function processarUpload(event) {
    const file = event.target.files && event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (loadEvent) {
        const img = new Image();
        img.onload = function () {
            let width = img.naturalWidth;
            let height = img.naturalHeight;
            const ratio = Math.min(500 / width, 200 / height, 1);
            width = Math.max(1, Math.round(width * ratio));
            height = Math.max(1, Math.round(height * ratio));
            canvasUpload.width = width;
            canvasUpload.height = height;
            ctxUpload.clearRect(0, 0, width, height);
            ctxUpload.drawImage(img, 0, 0, width, height);
            canvasUpload.style.display = 'block';
            document.querySelector('.upload-area').style.display = 'none';
        };
        img.src = loadEvent.target.result;
    };
    reader.readAsDataURL(file);
}

function limparAssinatura() {
    if (abaAtiva === 'desenhar') {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    } else if (abaAtiva === 'digitar') {
        document.getElementById('textoAssinatura').value = '';
        ctxTexto.clearRect(0, 0, canvasTexto.width, canvasTexto.height);
    } else {
        ctxUpload.clearRect(0, 0, canvasUpload.width, canvasUpload.height);
        document.getElementById('fileAssinatura').value = '';
        canvasUpload.style.display = 'none';
        document.querySelector('.upload-area').style.display = 'block';
    }
}

function isCanvasEmpty() {
    const pixels = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
    for (let index = 3; index < pixels.length; index += 4) {
        if (pixels[index] !== 0) return false;
    }
    return true;
}

function salvarAssinatura() {
    let signatureData = '';
    if (abaAtiva === 'desenhar' && !isCanvasEmpty()) {
        signatureData = canvas.toDataURL('image/png');
    } else if (abaAtiva === 'digitar' && document.getElementById('textoAssinatura').value.trim()) {
        atualizarPreviewTexto();
        signatureData = canvasTexto.toDataURL('image/png');
    } else if (abaAtiva === 'upload' && canvasUpload.style.display !== 'none' && canvasUpload.width > 0) {
        signatureData = canvasUpload.toDataURL('image/png');
    }
    if (!signatureData) {
        alert('Por favor, forneça sua assinatura antes de continuar.');
        return;
    }
    document.getElementById('conteudo-assinatura').innerHTML = `<img src="${signatureData}" alt="Assinatura da Contratante">`;
    registrarEvento('signature_saved', abaAtiva);
    fecharModal();
    tirarselfie();
}

function pararCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach((track) => track.stop());
        cameraStream = null;
    }
}

function setCameraStatus(message, isError) {
    const status = document.getElementById('cameraStatus');
    if (!status) return;
    status.textContent = message;
    status.classList.toggle('error', Boolean(isError));
}

function tirarselfie() {
    document.getElementById('modalSelfie').style.display = 'block';
    setCameraStatus('Aguardando permissão para acessar a câmera...', false);
    const video = document.getElementById('videoSelfie');
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        setCameraStatus('Este navegador não liberou a câmera. Toque em “Usar câmera/arquivo”.', true);
        return;
    }
    navigator.mediaDevices.getUserMedia({video: {facingMode: 'user'}, audio: false})
        .then(function (stream) {
            cameraStream = stream;
            video.srcObject = stream;
            return video.play();
        })
        .then(function () {
            setCameraStatus('Câmera pronta.', false);
            registrarEvento('camera_ready', 'getUserMedia');
        })
        .catch(function (error) {
            setCameraStatus('Não foi possível abrir a câmera. Toque em “Usar câmera/arquivo”.', true);
            registrarEvento('upload_failed', `camera:${error.name || 'erro'}`);
        });
}

function definirSelfie(dataUrl) {
    const area = document.getElementById('conteudo-selfie');
    area.innerHTML = `<img src="${dataUrl}" alt="Selfie da contratante">`;
    area.style.display = 'flex';
    registrarEvento('selfie_ready', String(dataUrl.length));
    finalizaSelfie();
}

function capturarSelfie() {
    const video = document.getElementById('videoSelfie');
    if (!video.videoWidth || !video.videoHeight) {
        alert('A câmera ainda não está pronta. Aguarde ou use o botão “Usar câmera/arquivo”.');
        return;
    }
    const selfieCanvas = document.createElement('canvas');
    selfieCanvas.width = 360;
    selfieCanvas.height = 480;
    selfieCanvas.getContext('2d').drawImage(video, 0, 0, 360, 480);
    definirSelfie(selfieCanvas.toDataURL('image/jpeg', 0.84));
}

function processarSelfieArquivo(event) {
    const file = event.target.files && event.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/') || file.size > 12 * 1024 * 1024) {
        alert('Escolha uma imagem válida de até 12 MB.');
        return;
    }
    const reader = new FileReader();
    reader.onload = function (loadEvent) {
        const image = new Image();
        image.onload = function () {
            const output = document.createElement('canvas');
            output.width = 360; output.height = 480;
            const outputContext = output.getContext('2d');
            const ratio = Math.max(360 / image.naturalWidth, 480 / image.naturalHeight);
            const width = image.naturalWidth * ratio;
            const height = image.naturalHeight * ratio;
            outputContext.drawImage(image, (360 - width) / 2, (480 - height) / 2, width, height);
            definirSelfie(output.toDataURL('image/jpeg', 0.84));
        };
        image.onerror = () => alert('Não foi possível abrir esta imagem.');
        image.src = loadEvent.target.result;
    };
    reader.readAsDataURL(file);
}

function finalizaSelfie() {
    pararCamera();
    document.getElementById('modalSelfie').style.display = 'none';
    gerarPDF();
}

function cancelarAssinatura() {
    pararCamera();
    document.getElementById('modalSelfie').style.display = 'none';
    location.reload();
}

function setProgress(title, detail, visible) {
    const progress = document.getElementById('pdfProgress');
    document.getElementById('pdfProgressTitle').textContent = title;
    document.getElementById('pdfProgressDetail').textContent = detail || 'Não feche esta página.';
    progress.hidden = !visible;
}

function criarDocumentoPDF() {
    const source = document.createElement('section');
    source.className = 'pdf-document';
    ['.header', '.texto-contrato', '.assinaturas-container', '#conteudo-selfie', '.container'].forEach((selector) => {
        const node = document.querySelector(selector);
        if (node) source.appendChild(node.cloneNode(true));
    });
    source.querySelectorAll('button,video,input').forEach((node) => node.remove());
    source.querySelectorAll('.texto-contrato > p,.texto-contrato > div,.texto-contrato > table,.texto-contrato > ul,.texto-contrato > ol,.assinaturas-container,.assinatura-info').forEach((node) => node.classList.add('pdf-keep-together'));
    return source;
}

function aguardarImagens(source) {
    const images = Array.from(source.querySelectorAll('img'));
    return Promise.all(images.map((image) => {
        if (image.complete) return Promise.resolve();
        return new Promise((resolve) => {
            image.onload = resolve;
            image.onerror = resolve;
            setTimeout(resolve, 5000);
        });
    }));
}

async function enviarPDF(pdfBlob, uuid, nome) {
    const formData = new FormData();
    formData.append('arquivo', pdfBlob, `${uuid}.pdf`);
    const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
    const timeout = setTimeout(() => { if (controller) controller.abort(); }, 120000);
    try {
        const response = await fetch(`upload.php?uuid=${encodeURIComponent(uuid)}&nome=${encodeURIComponent(nome)}`, {
            method: 'POST', body: formData, credentials: 'same-origin',
            signal: controller ? controller.signal : undefined,
        });
        const text = await response.text();
        let data;
        try { data = JSON.parse(text); } catch (error) { throw new Error('Resposta inválida do servidor.'); }
        if (!response.ok || data.status !== 'success') {
            throw new Error(data.message || `Falha HTTP ${response.status}.`);
        }
        return data;
    } finally {
        clearTimeout(timeout);
    }
}

async function gerarPDF() {
    const uuid = userData('uuid');
    const nome = userData('nome');
    if (!uuid || typeof html2pdf !== 'function') {
        alert('O gerador do contrato não foi carregado. Atualize a página e tente novamente.');
        return;
    }
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const source = criarDocumentoPDF();
    registrarEvento('pdf_started', isIOS ? 'ios' : 'other');
    setProgress('Preparando o contrato...', 'A geração pode levar alguns segundos.', true);
    document.body.classList.add('pdf-rendering');
    try {
        await aguardarImagens(source);
        const options = {
            margin: [0.42, 0.48, 0.42, 0.48],
            filename: `${uuid}.pdf`,
            image: {type: 'jpeg', quality: isIOS ? 0.86 : 0.91},
            html2canvas: {
                scale: isIOS ? 1.15 : 1.45,
                useCORS: true,
                allowTaint: false,
                backgroundColor: '#ffffff',
                logging: false,
            },
            jsPDF: {unit: 'in', format: 'a4', orientation: 'portrait', compress: true},
            pagebreak: {
                mode: ['css', 'legacy'],
                avoid: ['.pdf-keep-together', '.texto-contrato p', '.texto-contrato li', '.assinaturas-container', '.assinatura-info'],
            },
        };
        const pdfBlob = await html2pdf().set(options).from(source).outputPdf('blob');
        if (!pdfBlob || pdfBlob.size < 1000) throw new Error('O PDF gerado ficou vazio.');
        registrarEvento('pdf_ready', String(pdfBlob.size));
        setProgress('Enviando o contrato...', 'Mantenha esta página aberta até a confirmação.', true);
        registrarEvento('upload_started', String(pdfBlob.size));
        await enviarPDF(pdfBlob, uuid, nome);
        setProgress('Contrato salvo!', 'Redirecionando para a confirmação...', true);
        window.location.replace('boas_vindas.php?sucesso=true');
    } catch (error) {
        const message = error && error.message ? error.message : 'Erro desconhecido';
        registrarEvento('upload_failed', message);
        setProgress('', '', false);
        alert(`Não foi possível salvar o contrato. ${message}\n\nSua assinatura continua nesta tela; verifique a internet e tente novamente.`);
    } finally {
        document.body.classList.remove('pdf-rendering');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    registrarEvento('page_ready', `${window.innerWidth}x${window.innerHeight}`);
});

try {
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    fetch('configurar_fuso_horario.php', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({timezone: timezone})}).catch(() => {});
} catch (error) {}
