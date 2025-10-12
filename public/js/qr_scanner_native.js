// Lightweight QR scanner using native browser APIs (for demo purposes)
// For production, use a library like html5-qrcode or qr-scanner.js

export async function startNativeQRScanner(videoElement, onResult, onError) {
  if (!navigator.mediaDevices || !window.ImageCapture) {
    onError('Camera or ImageCapture API not supported.');
    return;
  }
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
    videoElement.srcObject = stream;
    videoElement.setAttribute('playsinline', 'true');
    await videoElement.play();
    // For demo: just show video, no QR decode. Integrate a real QR lib for scanning.
    // onResult('QR_DEMO_RESULT');
  } catch (err) {
    onError('Unable to access camera: ' + err.message);
  }
}
