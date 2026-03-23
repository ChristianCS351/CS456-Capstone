// barcode_scanner.js

let html5QrCode = null;
let activeScannerId = null;

function initializeScanner(btnId, readerId, resultId, targetInputId = null) {
    const scanBtn = document.getElementById(btnId);
    const reader = document.getElementById(readerId);
    const scanResult = document.getElementById(resultId);
    const targetInput = targetInputId ? document.getElementById(targetInputId) : null;

    if (!scanBtn || !reader || !scanResult) return;

    scanBtn.addEventListener("click", async () => {
        // If THIS scanner is running, stop it
        if (activeScannerId === readerId && html5QrCode) {
            try {
                await html5QrCode.stop();
                await html5QrCode.clear();
            } catch (e) { }

            activeScannerId = null;
            reader.style.display = "none";
            scanBtn.innerHTML = `<i class="fa-solid fa-camera"></i> Scan`;
            scanResult.textContent = "";
            return;
        }

        // If ANOTHER scanner is running, stop that one first
        if (activeScannerId && html5QrCode) {
            try {
                await html5QrCode.stop();
                await html5QrCode.clear();

                // Reset the other scanner's UI
                const oldBtn = activeScannerId === "reader" ? document.getElementById("scan_btn") : document.getElementById("quick_scan_btn");
                const oldReader = document.getElementById(activeScannerId);
                const oldResult = activeScannerId === "reader" ? document.getElementById("scan_result") : document.getElementById("quick_scan_result");

                if (oldBtn) oldBtn.innerHTML = `<i class="fa-solid fa-camera"></i> Scan`;
                if (oldReader) oldReader.style.display = "none";
                if (oldResult) oldResult.textContent = "";

            } catch (e) { }
        }

        // Start THIS scanner
        reader.style.display = "block";
        scanBtn.innerHTML = `<i class="fa-solid fa-stop"></i> Stop`;
        scanResult.textContent = "Opening camera...";

        html5QrCode = new Html5Qrcode(readerId);

        let isHandlingScan = false;

        try {
            activeScannerId = readerId;

            // Use facingMode which prompts for permissions gracefully without needing getCameras first
            await html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    formatsToSupport: [
                        Html5QrcodeSupportedFormats.UPC_A,
                        Html5QrcodeSupportedFormats.UPC_E,
                        Html5QrcodeSupportedFormats.EAN_13,
                        Html5QrcodeSupportedFormats.EAN_8,
                        Html5QrcodeSupportedFormats.CODE_39,
                        Html5QrcodeSupportedFormats.CODE_128,
                        Html5QrcodeSupportedFormats.ITF,
                        Html5QrcodeSupportedFormats.QR_CODE
                    ]
                },
                async (decodedText) => {
                    if (isHandlingScan) return;
                    isHandlingScan = true;

                    scanResult.textContent = "Scanned: " + decodedText;

                    // Fetch the input dynamically to avoid relying on a cached, potentially stale DOM reference
                    const currentTargetInput = targetInputId ? document.getElementById(targetInputId) : null;

                    if (currentTargetInput) {
                        currentTargetInput.value = decodedText;
                    } else {
                        // For Quick Scan, check if item exists in DB
                        scanResult.textContent = "Checking database...";
                        try {
                            const res = await fetch(`tracking.php?check_barcode=${encodeURIComponent(decodedText)}`);
                            const data = await res.json();
                            if (data.exists) {
                                // Item exists, show modal to update expiration
                                scanResult.textContent = "Item found!";
                                document.getElementById("modal_item_name").textContent = data.name;
                                document.getElementById("modal_barcode").value = decodedText;
                                document.getElementById("quickScanModal").style.display = "flex";
                            } else {
                                // Item does not exist, populate add item form
                                scanResult.textContent = "New item. Please fill out details.";
                                document.getElementById("barcode").value = decodedText;

                                // Auto-fill from history if available
                                if (data.history_name) {
                                    document.querySelector("input[name='name']").value = data.history_name;
                                    document.querySelector("input[name='dairy']").value = data.history_dairy;
                                    scanResult.textContent = "Item recognized from history! Please fill out remaining details.";
                                }

                                // smooth scroll up to the form
                                document.getElementById("add_item_form").scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                        } catch (e) {
                            scanResult.textContent = "Error checking database.";
                        }
                    }

                    try {
                        await html5QrCode.stop();
                        await html5QrCode.clear();
                    } catch (e) { }

                    activeScannerId = null;
                    reader.style.display = "none";
                    scanBtn.innerHTML = `<i class="fa-solid fa-camera"></i> Scan`;
                },
                () => { } // Ignore continuous scan errors
            );

            scanResult.textContent = "Point camera at barcode...";
        } catch (err) {
            let errorMsg = err;
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                errorMsg = "Browser blocked camera access. HTTPS is required on mobile devices.";
            } else if (err.toString().includes("NotAllowedError") || err.toString().includes("Permission denied")) {
                errorMsg = "Camera permission was denied. Please allow access in your browser settings.";
            }

            scanResult.innerHTML = `<span style="color: #dc2626; font-size: 0.85rem; font-weight: 500;">Camera error: ${errorMsg}</span>`;
            scanBtn.innerHTML = `<i class="fa-solid fa-camera"></i> Scan`;
            reader.style.display = "none";
            activeScannerId = null;
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    // 1. Setup the inline form scanner
    initializeScanner("scan_btn", "reader", "scan_result", "barcode");

    // 2. Setup the Quick Scan generic scanner
    initializeScanner("quick_scan_btn", "quick_reader", "quick_scan_result", null);
});
