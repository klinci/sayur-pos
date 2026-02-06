<!DOCTYPE html>
<html>
<head>
    <title>Invoice POS Bitmap</title>
    <style>
        body {
            font-family: monospace;
            display: flex;
            gap: 30px;
            padding: 20px;
        }
        .form, .preview {
            width: 320px;
        }
        .item {
            display: grid;
            grid-template-columns: 1fr 60px 80px 30px;
            gap: 4px;
            margin-bottom: 6px;
        }
        canvas {
            border: 1px dashed #333;
            background: #fff;
        }
    </style>
</head>
<body>

<div class="form">
    <form method="POST" action="/invoice/print">
        @csrf
        <h3>Input Item</h3>
        <div id="items"></div>
        <button type="button" onclick="addItem()">+ Item</button>
        <br><br>
        <button type="submit">PRINT</button>
    </form>
</div>

<div class="preview">
    <h3>Preview 58mm</h3>
    <canvas id="canvas" width="384"></canvas>
</div>

<script>
let index = 0;
const logo = new Image();
logo.src = "/logo-bw-300.png";

function addItem() {
    const div = document.createElement('div');
    div.className = 'item';
    div.innerHTML = `
        <input name="items[${index}][name]" placeholder="Item" oninput="render()">
        <input name="items[${index}][qty]" type="number" placeholder="Qty" oninput="render()">
        <input name="items[${index}][price]" type="number" placeholder="Harga" oninput="render()">
        <button type="button" onclick="this.parentElement.remove(); render()">−</button>
    `;
    document.getElementById('items').appendChild(div);
    index++;
    render();
}

function render() {
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');

    canvas.height = 500 + document.querySelectorAll('.item').length * 50;
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#000';

    let y = 10;

    /* LOGO */
    if (logo.complete) {
        const w = 280;
        const h = logo.height * (w / logo.width);
        ctx.drawImage(logo, (canvas.width - w) / 2, y, w, h);
        y += h + 10;
    }

    ctx.font = 'bold 16px monospace';
    ctx.fillText('TOKO CONTOH', 110, y); y += 22;
    ctx.font = '12px monospace';
    ctx.fillText('Jl. Testing No. 123', 90, y); y += 18;
    ctx.fillText('------------------------------', 10, y); y += 18;

    let total = 0;

    document.querySelectorAll('.item').forEach(row => {
        const i = row.querySelectorAll('input');
        const name = i[0].value || '';
        const qty = parseInt(i[1].value || 0);
        const price = parseInt(i[2].value || 0);
        if (!name || qty <= 0 || price <= 0) return;

        const subtotal = qty * price;
        total += subtotal;

        ctx.fillText(
            name.substring(0,16).padEnd(16) +
            qty.toString().padStart(3) +
            ' x ' +
            price.toString().padStart(7),
            10, y
        );
        y += 18;
        ctx.fillText('   Subtotal: ' + subtotal, 10, y);
        y += 22;
    });

    ctx.fillText('------------------------------', 10, y); y += 18;
    ctx.font = 'bold 14px monospace';
    ctx.fillText('TOTAL : ' + total, 10, y);
}

addItem();
</script>

</body>
</html>
