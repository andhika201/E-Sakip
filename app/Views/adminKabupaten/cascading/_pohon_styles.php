<style>
    /* =========================================================
       POHON KINERJA — gaya bersama (dipakai view cetak & inline)
       Multi-warna diperhalus: tiap level beda warna, nada selaras
       ========================================================= */

    /* ---------- Struktur pohon ---------- */
    .tree-container {
        overflow-x: auto;
        padding-bottom: 24px;
    }
    .tree {
        display: inline-block;
        min-width: 100%;
    }
    .tree ul {
        padding-top: 20px;
        position: relative;
        display: flex;
        justify-content: center;
        padding-left: 0;
        margin: 0;
    }
    .tree li {
        text-align: center;
        list-style-type: none;
        position: relative;
        padding: 20px 6px 0 6px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* Garis penghubung */
    .tree li::before,
    .tree li::after {
        content: '';
        position: absolute;
        top: 0;
        right: 50%;
        border-top: 2px solid #cbd5e1;
        width: 50%;
        height: 20px;
    }
    .tree li::after {
        right: auto;
        left: 50%;
        border-left: 2px solid #cbd5e1;
    }
    .tree li:only-child::after,
    .tree li:only-child::before { display: none; }
    .tree li:only-child { padding-top: 0; }
    .tree li:first-child::before,
    .tree li:last-child::after { border: 0 none; }
    .tree li:last-child::before {
        border-right: 2px solid #cbd5e1;
        border-radius: 0 6px 0 0;
    }
    .tree li:first-child::after { border-radius: 6px 0 0 0; }
    .tree ul ul::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        border-left: 2px solid #cbd5e1;
        width: 0;
        height: 20px;
        transform: translateX(-50%);
    }

    /* ---------- Node ---------- */
    .tree-node {
        display: inline-flex;
        flex-direction: column;
        align-items: stretch;
        gap: 5px;
        width: 200px;
        transition: transform .2s ease;
    }
    .node-label {
        font-size: 8.5px;
        font-weight: 700;
        letter-spacing: .8px;
        text-transform: uppercase;
        opacity: .72;
        margin-bottom: 2px;
    }

    /* Node utama (terisi penuh) — bagikan bentuk dasar */
    .box-visi,
    .box-misi,
    .box-tujuan,
    .box-sasaran,
    .box-opd {
        color: #fff;
        border-radius: 10px;
        padding: 11px 13px;
        font-weight: 600;
        line-height: 1.4;
        box-shadow: 0 3px 8px rgba(15, 23, 42, .12);
    }

    /* Node sekunder (chip bernuansa lembut) — bagikan bentuk dasar */
    .box-ikt,
    .box-iks,
    .box-csf,
    .box-program {
        border-radius: 7px;
        padding: 6px 9px;
        font-size: 10.5px;
        line-height: 1.35;
        border: 1px solid transparent;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
    }

    /* Visi — indigo lembut */
    .box-visi {
        background: linear-gradient(135deg, #34487d 0%, #28365f 100%);
        font-size: 15px;
        border-radius: 12px;
        padding: 14px 18px;
    }
    /* Misi — teal lembut */
    .box-misi {
        background: linear-gradient(135deg, #2f8579 0%, #246b61 100%);
        font-size: 13.5px;
    }
    /* Tujuan RPJMD — hijau */
    .box-tujuan {
        background: linear-gradient(135deg, #43885d 0%, #356f4a 100%);
        font-size: 12.5px;
    }
    /* Indikator Tujuan — chip hijau muda */
    .box-ikt {
        background: #e9f3ed;
        color: #2c6b45;
        border-color: #cce3d5;
        margin-top: -1px;
    }
    /* Sasaran RPJMD — coklat hangat */
    .box-sasaran {
        background: linear-gradient(135deg, #9a6a44 0%, #7e5334 100%);
        font-size: 12.5px;
    }
    /* Indikator Sasaran — chip amber muda */
    .box-iks {
        background: #fbeede;
        color: #8f561d;
        border-color: #f0d6b4;
        margin-top: -1px;
    }
    /* CSF — catatan krem */
    .box-csf {
        background: #fff7e9;
        color: #8a5a14;
        border-color: #f0dcaf;
        text-align: left;
        margin-bottom: 1px;
    }
    /* OPD — mauve lembut */
    .box-opd {
        background: linear-gradient(135deg, #6f5f8a 0%, #574a6e 100%);
        font-size: 12px;
    }
    /* Program — chip biru muda */
    .box-program {
        background: #e9eef7;
        color: #3a548a;
        border-color: #cfd9ec;
        text-align: left;
        margin-top: -1px;
    }

    /* ---------- Legenda ---------- */
    .pohon-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px 14px;
        margin: 0 auto 22px;
        padding: 12px 18px;
        background: #f8fafc;
        border: 1px solid #e6eaf0;
        border-radius: 12px;
    }
    .pohon-legend .lg-title {
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        align-self: center;
    }
    .pohon-legend .lg-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        color: #334155;
    }
    .pohon-legend .lg-swatch {
        width: 18px;
        height: 18px;
        border-radius: 5px;
        flex: 0 0 auto;
    }

    /* Responsif: node lebih ringkas di layar kecil (tetap bisa scroll + zoom) */
    @media (max-width: 768px) {
        .tree-node { width: 160px; }
        .tree li { padding: 16px 4px 0 4px; }
        .pohon-legend { gap: 6px 10px; padding: 10px 12px; }
    }
    @media (max-width: 480px) {
        .tree-node { width: 140px; }
    }
</style>
