Alpine.store('nav', { currentPage: 'home' });
const scanppf = document.getElementById("scan-ppf");

scanppf.addEventListener("click", function () {
    if (scanning == true) {
        return;
    }

    navigator.mediaDevices
        .getUserMedia({ video: { facingMode: { ideal: "environment" } } })
        .then((stream) => {
            stream.getTracks().forEach((track) => track.stop());

            scanning = true;
            codeReader.decodeFromConstraints(
                {
                    video: { facingMode: { ideal: "environment" } },
                },
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
            console.error("Camera error:", err);
            alert("Camera permission denied or unavailable.");
            scanning = false;
        });
});