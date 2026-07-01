Alpine.store('nav', { currentPage: 'home' });
const scanppf = document.getElementById("scan-ppf");

scanppf.addEventListener("click", function () {
    if (scanning == true) {
        return;
    }
    navigator.mediaDevices
        .enumerateDevices()
        .then((devices) => {
            const videoInputDevices = devices.filter(
                (device) => device.kind === "videoinput",
            );

            if (videoInputDevices.length === 0) {
                alert("No Camera found.");
                scanning = false;
                return;
            }
            navigator.mediaDevices
                .getUserMedia({ video: true })
                .then(() => {
                    return navigator.mediaDevices.enumerateDevices();
                })
                .then((devices) => {
                    console.log(devices);
                })
                .catch((err) => {
                    console.error("Permission or device error:", err);
                });

            const selectedDeviceId = videoInputDevices[0].deviceId;
            scanning = true;
            codeReader.decodeFromVideoDevice(
                selectedDeviceId,
                "video",
                (result, err) => {
                    if (result) {
                        const qrcode = document.getElementById("PPF");
                        const scannedPPF = result.getText().trim();
                        qrcode.value = scannedPPF;
                        qrcode.dispatchEvent(new Event("input"));
                        Livewire.dispatch("post-ppf", { ppf: scannedPPF });
                        qrcode.focus();
                        codeReader.reset();
                        scanning = false;

                        document.getElementById("scanner-id-close").click();
                        return;
                    }
                    if (err && !(err instanceof ZXing.NotFoundException)) {
                        console.error(err);
                    }
                },
            );
        })
        .catch((err) => {
            console.error("error", err);
            scanning = false;
        });
});
