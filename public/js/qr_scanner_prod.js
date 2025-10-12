// QR code scanning using qr-scanner library
// https://github.com/nimiq/qr-scanner
import QrScanner from 'https://cdn.jsdelivr.net/npm/qr-scanner@1.4.2/qr-scanner.min.js';

export function startQRScanner(videoElement, onResult, onError) {
  QrScanner.hasCamera().then(hasCamera => {
    if (!hasCamera) {
      onError('No camera found.');
      return;
    }
    const scanner = new QrScanner(
      videoElement,
      result => {
        onResult(result.data);
        scanner.stop();
      },
      {
        onDecodeError: error => {
          // Ignore decode errors, keep scanning
        },
        highlightScanRegion: true,
        highlightCodeOutline: true
      }
    );
    scanner.start().catch(err => onError('Unable to start camera: ' + err.message));
  });
}
