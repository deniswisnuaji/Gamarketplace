/* GaMarketplace — "Price Range" button with min / max inputs (used by Marketplace and Bidding Arena) */
/* ============ PRICE RANGE POPOVER ============ */
// Lets the user type their own min / max price. getRange() reads the current range,
// onApply(min, max) saves it (null = no limit).
function setupPricePopover({ getRange, onApply, emptyText }) {
  const pop = document.getElementById("pricePop");
  const btn = document.getElementById("priceBtn");
  const panel = document.getElementById("pricePanel");
  const minI = document.getElementById("popMin");
  const maxI = document.getElementById("popMax");
  const err = document.getElementById("popError");
  const text = document.getElementById("priceBtnText");

  const fmt = (n) => (n === null || n === undefined ? "" : n.toLocaleString("id-ID"));
  const parse = (v) => { const d = String(v).replace(/\D/g, ""); return d === "" ? null : Number(d); };
  const short = (n) => (n >= 1e6 ? `${(n / 1e6).toLocaleString("id-ID", { maximumFractionDigits: 1 })}jt` : n >= 1000 ? `${Math.round(n / 1000)}rb` : String(n));
  const label = (min, max) => {
    if (min === null && max === null) return emptyText;
    if (min === null) return `Up to Rp ${short(max)}`;
    if (max === null) return `From Rp ${short(min)}`;
    return `Rp ${short(min)} – ${short(max)}`;
  };

  function sync() {
    const { min, max } = getRange();
    text.textContent = label(min, max);
    btn.classList.toggle("is-set", min !== null || max !== null);
  }
  function open() {
    const { min, max } = getRange();
    minI.value = fmt(min);
    maxI.value = fmt(max);
    err.hidden = true;
    panel.hidden = false;
    btn.setAttribute("aria-expanded", "true");
    minI.focus();
  }
  function close(focusBtn) {
    if (panel.hidden) return;
    panel.hidden = true;
    btn.setAttribute("aria-expanded", "false");
    if (focusBtn) btn.focus();
  }
  function apply() {
    const min = parse(minI.value);
    const max = parse(maxI.value);
    if (min !== null && max !== null && min > max) {
      err.textContent = "Min price can't be higher than max price.";
      err.hidden = false;
      minI.focus();
      return;
    }
    onApply(min, max);
    sync();
    close(true);
  }

  btn.addEventListener("click", () => (panel.hidden ? open() : close()));
  // Show thousand separators while typing (100000 -> 100.000)
  [minI, maxI].forEach((input) => input.addEventListener("input", () => {
    input.value = fmt(parse(input.value));
    err.hidden = true;
  }));
  panel.addEventListener("keydown", (e) => {
    if (e.key === "Enter") { e.preventDefault(); apply(); }
    if (e.key === "Escape") { e.stopPropagation(); close(true); }
  });
  panel.querySelector("[data-apply]").addEventListener("click", apply);
  panel.querySelector("[data-clear]").addEventListener("click", () => { minI.value = ""; maxI.value = ""; onApply(null, null); sync(); close(true); });
  panel.querySelectorAll("[data-preset]").forEach((b) => b.addEventListener("click", () => {
    const [lo, hi] = b.dataset.preset.split("-");
    minI.value = fmt(parse(lo));
    maxI.value = fmt(parse(hi));
    apply();
  }));
  document.addEventListener("click", (e) => { if (!pop.contains(e.target)) close(false); });

  sync();
  return { sync };
}
