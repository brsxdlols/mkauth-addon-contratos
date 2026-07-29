// Variáveis globais
let abaAtiva = 'desenhar';
let assinaturaData = null;

// Configurações do Canvas de Desenho
const canvas = document.getElementById("signatureCanvas");
const ctx = canvas.getContext("2d");
let drawing = false;

ctx.lineWidth = 2;
ctx.lineCap = "round";
ctx.strokeStyle = "#000000";

// Canvas de Texto
const canvasTexto = document.getElementById("canvasTexto");
const ctxTexto = canvasTexto ? canvasTexto.getContext("2d") : null;

// Canvas de Upload
const canvasUpload = document.getElementById("canvasUpload");
const ctxUpload = canvasUpload ? canvasUpload.getContext("2d") : null;

function getCoordinates(event) {
  const rect = canvas.getBoundingClientRect();
  const scaleX = canvas.width / rect.width;
  const scaleY = canvas.height / rect.height;

  if (event.touches) {
    const touch = event.touches[0];
    return {
      x: (touch.clientX - rect.left) * scaleX,
      y: (touch.clientY - rect.top) * scaleY,
    };
  } else {
    return {
      x: (event.clientX - rect.left) * scaleX,
      y: (event.clientY - rect.top) * scaleY,
    };
  }
}

function startDrawing(event) {
  drawing = true;
  const coords = getCoordinates(event);
  ctx.moveTo(coords.x, coords.y);
  event.preventDefault();
}

function draw(event) {
  if (drawing) {
    const coords = getCoordinates(event);
    ctx.lineTo(coords.x, coords.y);
    ctx.stroke();
  }
}

function stopDrawing() {
  drawing = false;
  ctx.beginPath();
}

canvas.addEventListener("mousedown", startDrawing);
canvas.addEventListener("mousemove", draw);
canvas.addEventListener("mouseup", stopDrawing);
canvas.addEventListener("mouseout", stopDrawing);

canvas.addEventListener("touchstart", startDrawing);
canvas.addEventListener("touchmove", draw);
canvas.addEventListener("touchend", stopDrawing);

function abrirModal() {
  document.getElementById("modalAssinatura").style.display = "block";
}

function fecharModal() {
  document.getElementById("modalAssinatura").style.display = "none";
}

window.onclick = function (event) {
  var modal = document.getElementById("modalAssinatura");
  if (event.target === modal) {
    modal.style.display = "none";
  }
};

// Função para mudar de aba
function mudarAba(aba) {
  abaAtiva = aba;
  
  // Atualizar botões
  document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
  event.target.classList.add('active');
  
  // Atualizar conteúdo
  document.querySelectorAll('.aba-content').forEach(content => content.style.display = 'none');
  document.getElementById(`aba-${aba}`).style.display = 'block';
}

// Aba Digitar - Atualizar preview do texto
function atualizarPreviewTexto() {
  const texto = document.getElementById('textoAssinatura').value;
  const fonte = document.getElementById('fonteAssinatura').value;
  
  if (!texto || !canvasTexto) return;
  
  ctxTexto.clearRect(0, 0, canvasTexto.width, canvasTexto.height);
  ctxTexto.font = `48px ${fonte}`;
  ctxTexto.fillStyle = '#000';
  ctxTexto.textAlign = 'center';
  ctxTexto.textBaseline = 'middle';
  ctxTexto.fillText(texto, canvasTexto.width / 2, canvasTexto.height / 2);
}

// Event listener para atualizar preview em tempo real
if (document.getElementById('textoAssinatura')) {
  document.getElementById('textoAssinatura').addEventListener('input', atualizarPreviewTexto);
}

// Aba Upload - Processar imagem
function processarUpload(event) {
  const file = event.target.files[0];
  if (!file) return;
  
  const reader = new FileReader();
  reader.onload = function(e) {
    const img = new Image();
    img.onload = function() {
      // Mostrar canvas
      canvasUpload.style.display = 'block';
      
      // Ajustar tamanho do canvas
      const maxWidth = 500;
      const maxHeight = 200;
      let width = img.width;
      let height = img.height;
      
      if (width > maxWidth) {
        height = (maxWidth / width) * height;
        width = maxWidth;
      }
      if (height > maxHeight) {
        width = (maxHeight / height) * width;
        height = maxHeight;
      }
      
      canvasUpload.width = width;
      canvasUpload.height = height;
      
      // Desenhar imagem
      ctxUpload.clearRect(0, 0, canvasUpload.width, canvasUpload.height);
      ctxUpload.drawImage(img, 0, 0, width, height);
      
      // Ocultar área de upload
      document.querySelector('.upload-area').style.display = 'none';
    };
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
}

function clearCanvas() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
}

function limparAssinatura() {
  if (abaAtiva === 'desenhar') {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
  } else if (abaAtiva === 'digitar') {
    document.getElementById('textoAssinatura').value = '';
    if (ctxTexto) ctxTexto.clearRect(0, 0, canvasTexto.width, canvasTexto.height);
  } else if (abaAtiva === 'upload') {
    if (ctxUpload) ctxUpload.clearRect(0, 0, canvasUpload.width, canvasUpload.height);
    document.getElementById('fileAssinatura').value = '';
    canvasUpload.style.display = 'none';
    document.querySelector('.upload-area').style.display = 'block';
  }
}

function salvarAssinatura() {
  let signatureData = null;
  let isEmpty = true;
  
  // Verificar qual aba está ativa e obter assinatura
  if (abaAtiva === 'desenhar') {
    isEmpty = isCanvasEmpty();
    if (!isEmpty) signatureData = canvas.toDataURL("image/png");
  } else if (abaAtiva === 'digitar') {
    const texto = document.getElementById('textoAssinatura').value.trim();
    if (texto) {
      atualizarPreviewTexto();
      signatureData = canvasTexto.toDataURL("image/png");
      isEmpty = false;
    }
  } else if (abaAtiva === 'upload') {
    if (canvasUpload.style.display !== 'none') {
      signatureData = canvasUpload.toDataURL("image/png");
      isEmpty = false;
    }
  }

  if (isEmpty) {
    alert("Por favor, forneça sua assinatura antes de continuar.");
    return;
  }

  document.getElementById("conteudo-assinatura").innerHTML = `<img src="${signatureData}" alt="Assinatura da Contratante">`;
  fecharModal();
  tirarselfie();
}

function isCanvasEmpty() {
  const emptyCanvasData = ctx.getImageData(0, 0, canvas.width, canvas.height);
  const data = emptyCanvasData.data;

  for (let i = 0; i < data.length; i += 4) {
    if (data[i + 3] !== 0) {
      return false;
    }
  }
  return true;
}

function tirarselfie() {
  const modal = document.getElementById("modalSelfie");
  modal.style.display = "block";

  const video = document.getElementById("videoSelfie");

  navigator.mediaDevices
    .getUserMedia({ video: true })
    .then(function (stream) {
      video.srcObject = stream;
      video.onloadedmetadata = () => {
        video.play();
      };
    })
    .catch(function (err) {
      console.error("Erro ao acessar a câmera: ", err);
    });
}

function capturarSelfie() {
  const video = document.getElementById("videoSelfie");

  if (video.videoWidth === 0 || video.videoHeight === 0) {
    alert("A câmera ainda não está pronta. Tente novamente.");
    return;
  }

  const canvas = document.createElement("canvas");
  canvas.width = 180;
  canvas.height = 240;

  const ctx = canvas.getContext("2d");
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

  const selfieData = canvas.toDataURL("image/png");

  if (!selfieData || selfieData.length < 1000) {
    alert("Erro ao capturar a selfie. Tente novamente.");
    return;
  }

  const conteudoSelfie = document.getElementById("conteudo-selfie");
  conteudoSelfie.innerHTML = `<img src="${selfieData}" alt="Selfie">`;
  conteudoSelfie.style.display = "flex";
  finalizaSelfie();
}

function finalizaSelfie() {
  document.getElementById("modalSelfie").style.display = "none";
  gerarPDF();
}

function cancelarAssinatura() {
  document.getElementById("modalSelfie").style.display = "none";
  location.reload();
}

function gerarPDF() {
  const conteudoParaPDF = document.body;
  const uuid = document.getElementById("user-data").getAttribute("data-uuid");
  const nome = document.getElementById("user-data").getAttribute("data-nome");

  // Detectar iOS para ajustar escala
  const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
  const escala = isIOS ? 2 : 1.5; // iOS precisa de escala maior para compensar

  const options = {
    margin: 0.7,
    filename: `${uuid}.pdf`,
    image: {
      type: "jpeg",
      quality: 0.98,
    },
    html2canvas: {
      scale: escala,
      useCORS: true,
      allowTaint: false,
      ignoreElements: (el) => el.tagName === "VIDEO" || el.id === "modalSelfie",
      windowWidth: document.body.scrollWidth,
      windowHeight: document.body.scrollHeight,
    },
    jsPDF: {
      unit: "in",
      format: "a4",
      orientation: "portrait",
    },
    pagebreak: {
      mode: ["css", "legacy", "avoid-all"],
      avoid: [".texto-contrato p", ".texto-contrato h1", ".texto-contrato h2", ".assinaturas-container", ".assinatura-info"],
    },
  };

  html2pdf()
    .set(options)
    .from(conteudoParaPDF)
    .outputPdf("blob")
    .then((pdfBlob) => {
      const formData = new FormData();
      formData.append("arquivo", pdfBlob, `${uuid}.pdf`);

      return fetch(`${window.location.origin}/admin/addons/contratos/upload.php?uuid=${uuid}&nome=${encodeURIComponent(nome)}`, {
        method: "POST",
        body: formData,
      });
    })
    .then((response) => {
      if (!response.ok) throw new Error("Erro ao enviar o PDF");
      return response.json();
    })
    .then((data) => {
      console.log("PDF enviado com sucesso:", data);
      // Redirecionar para página de sucesso
      window.location.href = 'boas_vindas.php?sucesso=true';
    })
    .catch((error) => {
      console.error("Erro:", error);
      alert("Erro ao enviar o contrato. Por favor, tente novamente.");
    });
}

// Envio do fuso horário
const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

fetch('configurar_fuso_horario.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ timezone }),
})
.then(response => response.json())
.then(data => console.log(data));
