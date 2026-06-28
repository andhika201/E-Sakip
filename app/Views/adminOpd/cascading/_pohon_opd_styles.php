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
        width: 180px;
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

    /* Tujuan RPJMD — teal */
    .box-l1 {
        background: linear-gradient(135deg, #2f8579 0%, #246b61 100%);
        font-size: 12.5px;
        border-radius: 11px;
    }
    /* Sasaran RPJMD — hijau */
    .box-l2 { background: linear-gradient(135deg, #43885d 0%, #356f4a 100%); }
    /* Tujuan Renstra — biru */
    .box-l3 { background: linear-gradient(135deg, #3f6296 0%, #2f4d7a 100%); }
    /* Sasaran ESS II — coklat hangat */
    .box-es2 { background: linear-gradient(135deg, #9a6a44 0%, #7e5334 100%); }
    /* Sasaran ESS III — mauve */
    .box-es3 { background: linear-gradient(135deg, #6f5f8a 0%, #574a6e 100%); }
    /* Sasaran ESS IV — slate biru */
    .box-es4 { background: linear-gradient(135deg, #41709e 0%, #2f5680 100%); }

    /* Indikator kinerja — chip amber */
    .box-iks {
        background: #fbeede;
        color: #8f561d;
        border-color: #f0d6b4;
        text-align: left;
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
