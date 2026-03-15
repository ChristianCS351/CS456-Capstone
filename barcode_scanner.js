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
            } catch (e) {}

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

            } catch (e) {}
        }

        // Start THIS scanner
        reader.style.display = "block";
        scanBtn.innerHTML = `<i class="fa-solid fa-stop"></i> Stop`;
        scanResult.textContent = "Opening camera...";

        html5QrCode = new Html5Qrcode(readerId);

        try {
            const cameras = await Html5Qrcode.getCameras();

            if (!cameras || cameras.length === 0) {
                scanResult.textContent = "No camera found on this device.";
                scanBtn.innerHTML = `<i class="fa-solid fa-camera"></i> Scan`;
                reader.style.display = "none";
                activeScannerId = null;
                return;
            }

            // Prefer "back" camera
            const camId = cameras[cameras.length - 1].id;
            activeScannerId = readerId;

            await html5QrCode.start(
                camId,
                { fps: 10, qrbox: { width: 250, height: 250 } },
                async (decodedText) => {
                    scanResult.textContent = "Scanned: " + decodedText;

                    if (targetInput) {
                        targetInput.value = decodedText;
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
                    } catch (e) {}

                    activeScannerId = null;
                    reader.style.display = "none";
                    scanBtn.innerHTML = `<i class="fa-solid fa-camera"></i> Scan`;
                },
                () => {} // Ignore continuous scan errors
            );

            scanResult.textContent = "Point camera at barcode...";
        } catch (err) {
            scanResult.textContent = "Camera error: " + err;
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
