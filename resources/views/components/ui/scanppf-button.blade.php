        <div class="flex-shrink-0 mx-5 sm:mx-2"
            x-data="{
    scanning: false,
    codeReader: null,
    stream: null,
    init() {
        this.codeReader = new ZXing.BrowserMultiFormatReader();
    },
    stopScan() {
        this.codeReader.reset();
        this.scanning = false;
        this.stopCamera();
    },
    stopCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
        const video = document.getElementById('video');
        if (video) {
            video.srcObject = null;
        }
    },
    async startScan() {
        if (this.scanning) return;
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoInputDevices = devices.filter(d => d.kind === 'videoinput');

            if (videoInputDevices.length === 0) {
                alert('No Camera found.');
                return;
            }

            this.stream = await navigator.mediaDevices.getUserMedia({ video: true });

            this.scanning = true;
            const selectedDeviceId = videoInputDevices[0].deviceId;

            this.codeReader.decodeFromVideoDevice(
                selectedDeviceId,
                'video',
                (result, err) => {
                    if (result) {
                        const scannedPPF = result.getText().trim();

                        const qrcode = document.getElementById('PPF');
                        qrcode.value = scannedPPF;
                        qrcode.dispatchEvent(new Event('input'));
                        qrcode.focus();

                        $wire.dispatch('post-ppf', { ppf: scannedPPF });

                        this.stopScan();
                        $dispatch('close-scanner-modal');
                        return;
                    }
                }
            );
        } catch (err) {
            console.error('error', err);
            this.scanning = false;
        }
    }
}"
            @close-scanner-modal.window="stopScan()">
            <button
                type="button"
                id="scan-ppf"
                style="height: 45px;"
                :disabled="scanning"
                @click="$dispatch('open-scanner-modal'); startScan()"
                class="bg-[#0F3C89] hover:bg-blue-800 
               text-white font-medium rounded-lg text-sm 
               px-6 py-2 w-full transition duration-200 
               focus:ring-4 focus:ring-blue-300
               disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-text="scanning ? 'Scanning...' : 'Scan PPF'"></span>
            </button>
        </div>