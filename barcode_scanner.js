// quagga_scanner.js

let quagga_running = false;

document.addEventListener("DOMContentLoaded", () => {
    const scan_btn = document.getElementById("scan_btn");
    const scanner_box = document.getElementById("scanner");
    const result_span = document.getElementById("result");
    const error_p = document.getElementById("scan_error");
    const barcode_input = document.getElementById("barcode");

    if (!scan_btn || !scanner_box || !result_span || !barcode_input) {
        return;
    }

    function start_quagga() {
        error_p.textContent = "";
        result_span.textContent = "";
        scanner_box.style.display = "block";
        scan_btn.textContent = "STOP";

        Quagga.init({
            inputStream: {
                type: "LiveStream",
                target: scanner_box,
                constraints: {
                    facingMode: "environment"
                }
            },
            decoder: {
                readers: ["upc_reader", "ean_reader", "code_128_reader"]
            },
            locate: true
        }, function(err) {
            if (err) {
                console.error(err);
                error_p.textContent = "Camera error: " + (err.message || err);
                scanner_box.style.display = "none";
                scan_btn.textContent = "SCAN";
                quagga_running = false;
                return;
            }

            Quagga.start();
            quagga_running = true;
        });
    }

    function stop_quagga() {
        try {
            Quagga.stop();
        } catch (e) {
            // ignore
        }

        quagga_running = false;
        scanner_box.style.display = "none";
        scan_btn.textContent = "SCAN";
    }

    // When a barcode is detected
    Quagga.onDetected((data) => {
        const code = data?.codeResult?.code;
        if (!code) return;

        // prevent rapid double-fires
        if (result_span.textContent === code) return;

        result_span.textContent = code;
        barcode_input.value = code;

        // OPTIONAL: stop after first successful scan
        stop_quagga();

        // OPTIONAL: auto-submit after scan
        // document.getElementById("add_item_form").submit();
    });

    scan_btn.addEventListener("click", () => {
        if (quagga_running) {
            stop_quagga();
        } else {
            start_quagga();
        }
    });
});
