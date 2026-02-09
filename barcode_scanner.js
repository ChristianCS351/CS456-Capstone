// barcode_scanner.js

let scanner_running = false;
let html5QrCode = null;

document.addEventListener("DOMContentLoaded", () => {
    const scan_btn = document.getElementById("scan_btn");
    const reader = document.getElementById("reader");
    const scan_result = document.getElementById("scan_result");
    const barcode_input = document.getElementById("barcode");

    // just in case someone loads this JS on another page
    if (!scan_btn || !reader || !scan_result || !barcode_input) {
        return;
    }

    scan_btn.addEventListener("click", async () => {
        // If scanning, stop scanning
        if (scanner_running && html5QrCode) {
            try {
                await html5QrCode.stop();
                await html5QrCode.clear();
            } catch (e) {
                // ignore
            }

            scanner_running = false;
            reader.style.display = "none";
            scan_btn.textContent = "SCAN";
            scan_result.textContent = "";
            return;
        }

        // Start scanning
        reader.style.display = "block";
        scan_btn.textContent = "STOP";
        scan_result.textContent = "Opening camera...";

        html5QrCode = new Html5Qrcode("reader");

        try {
            const cameras = await Html5Qrcode.getCameras();

            if (!cameras || cameras.length === 0) {
                scan_result.textContent = "No camera found on this device.";
                scan_btn.textContent = "SCAN";
                reader.style.display = "none";
                scanner_running = false;
                return;
            }

            // Prefer "back" camera when possible (usually last on phones)
            const cam_id = cameras[cameras.length - 1].id;

            scanner_running = true;

            await html5QrCode.start(
                cam_id,
                { fps: 10, qrbox: { width: 250, height: 250 } },
                async (decodedText) => {
                    // When something scans successfully
                    scan_result.textContent = "Scanned: " + decodedText;

                    // Put it into your hidden field so it saves to DB when you submit
                    barcode_input.value = decodedText;

                    // Stop after successful scan
                    try {
                        await html5QrCode.stop();
                        await html5QrCode.clear();
                    } catch (e) {
                        // ignore
                    }

                    scanner_running = false;
                    reader.style.display = "none";
                    scan_btn.textContent = "SCAN";

                    // OPTIONAL: auto-submit form after scan
                    // document.getElementById("add_item_form").submit();
                },
                () => {
                    // scan errors happen constantly while it’s trying to detect
                    // leave blank so console doesn't get spammed
                }
            );

            scan_result.textContent = "Point camera at barcode...";
        } catch (err) {
            scan_result.textContent = "Camera error: " + err;
            scan_btn.textContent = "SCAN";
            reader.style.display = "none";
            scanner_running = false;
        }
    });
});
