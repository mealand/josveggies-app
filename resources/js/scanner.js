import QrScanner from 'qr-scanner';

/**
 * Camera QR scanning for pickup verification at the Abuja hub.
 * On a successful read the code is dispatched to the Livewire pickup component.
 */
export function initScanner(container) {
    const video = container.querySelector('video');
    const startButton = container.querySelector('[data-scan-start]');
    const stopButton = container.querySelector('[data-scan-stop]');
    const status = container.querySelector('[data-scan-status]');
    let scanner = null;
    let lastCode = null;

    const setStatus = (text) => {
        if (status) {
            status.textContent = text;
        }
    };

    const stop = () => {
        scanner?.stop();
        container.classList.remove('is-scanning');
    };

    const start = async () => {
        if (!(await QrScanner.hasCamera())) {
            setStatus('No camera found — type the code instead.');
            return;
        }

        scanner ??= new QrScanner(
            video,
            (result) => {
                const code = String(result.data || '').replace(/^JV:/, '').trim();
                if (!code || code === lastCode) {
                    return;
                }
                lastCode = code;
                setTimeout(() => (lastCode = null), 4000);
                navigator.vibrate?.(60);
                window.Livewire?.dispatch('pickup-code-scanned', { code });
                setStatus(`Scanned ${code}`);
            },
            { highlightScanRegion: true, highlightCodeOutline: true, preferredCamera: 'environment', maxScansPerSecond: 4 },
        );

        try {
            await scanner.start();
            container.classList.add('is-scanning');
            setStatus('Point the camera at the customer’s QR code');
        } catch {
            setStatus('Camera permission denied — type the code instead.');
        }
    };

    startButton?.addEventListener('click', start);
    stopButton?.addEventListener('click', stop);
    document.addEventListener('livewire:navigating', stop, { once: true });
}
