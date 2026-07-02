<style>
    /* =========================================================
       POHON KINERJA OPD — gaya bersama (view cetak & inline)
       Tujuan RPJMD > Sasaran RPJMD > Tujuan Renstra > ESS II/III/IV
       Multi-warna diperhalus: tiap level beda warna, nada selaras
       ========================================================= */

    /* ---------- Struktur pohon ---------- */
    .tree-container { overflow-x: auto; padding-bottom: 24px; }
    .tree { display: inline-block; min-width: 100%; }
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
    .tree li::before,
    .tree li::after {
        content: '';
        position: absolute;
        top: 0;
        right: 50%;
        border-top: 2px solid #000;
        width: 50%;
        height: 20px;
    }
    .tree li::after {
        right: auto;
        left: 50%;
        border-left: 2px solid #000;
    }
    .tree li:only-child::after,
    .tree li:only-child::before { display: none; }
    .tree li:only-child { padding-top: 0; }
    .tree li:first-child::before,
    .tree li:last-child::after { border: 0 none; }
    .tree li:last-child::before {
        border-right: 2px solid #000;
        border-radius: 0 6px 0 0;
    }
    .tree li:first-child::after { border-radius: 6px 0 0 0; }
    .tree ul ul::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        border-left: 2px solid #000;
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
        width: 180px;
        transition: transform .2s ease;
    }
    /* Label kategori node (TUJUAN/SASARAN/ESS/CSF/dll) disembunyikan (permintaan user) */
    .node-label {
        display: none;
    }

    /* Node utama (terisi) */
    .box-l1, .box-l2, .box-l3, .box-es2, .box-es3, .box-es4 {
        color: #fff;
        border-radius: 10px;
        padding: 10px 12px;
        font-weight: 600;
        line-height: 1.4;
        font-size: 12px;
        box-shadow: 0 3px 8px rgba(15, 23, 42, .12);
    }
    /* Node sekunder (chip lembut) */
    .box-iks, .box-csf {
        border-radius: 7px;
        padding: 5px 8px;
        font-size: 10px;
        line-height: 1.35;
        border: 1px solid transparent;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
    }

    /* Warna antar-level dibuat KONTRAS: tiap tingkat beda hue jelas.
       Hijau → Teal → Biru → Oranye → Ungu → Merah-rose. */
    /* Tujuan RPJMD — hijau */
    .box-l1 {
        background: linear-gradient(135deg, #15803d 0%, #166534 100%);
        font-size: 12.5px;
        border-radius: 11px;
    }
    /* Sasaran RPJMD — teal */
    .box-l2 { background: linear-gradient(135deg, #0f766e 0%, #115e59 100%); }
    /* Tujuan Renstra — biru */
    .box-l3 { background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); }
    /* Sasaran ESS II — oranye */
    .box-es2 { background: linear-gradient(135deg, #c2410c 0%, #9a3412 100%); }
    /* Sasaran ESS III — ungu */
    .box-es3 { background: linear-gradient(135deg, #9333ea 0%, #7e22ce 100%); }
    /* Sasaran ESS IV — merah rose */
    .box-es4 { background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); }

    /* Indikator kinerja — chip netral */
    .box-iks {
        background: #eef2f5;
        color: #39493f;
        border-color: #dbe4de;
        text-align: left;
        margin-top: -1px;
    }
    /* Kode indikator (label "IK" di depan nama) */
    .ind-kode {
        display: inline-block;
        font-weight: 800;
        font-size: .72em;
        letter-spacing: .6px;
        padding: 1px 6px;
        margin-right: 5px;
        border-radius: 5px;
        background: #00743e;
        color: #fff;
        vertical-align: middle;
    }
    /* CSF — catatan krem lembut */
    .box-csf {
        background: #faf3e6;
        color: #7a5a1e;
        border-color: #ecdcb8;
        text-align: left;
        margin-bottom: 1px;
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
        .tree-node { width: 150px; }
        .tree li { padding: 16px 4px 0 4px; }
        .pohon-legend { gap: 6px 10px; padding: 10px 12px; }
    }
    @media (max-width: 480px) {
        .tree-node { width: 132px; }
    }
</style>
