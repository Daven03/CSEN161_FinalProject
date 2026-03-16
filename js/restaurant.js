function storageKey() {
  return `scrollY:${document.title}`;
}

function saveScrollPosition(y) {
  localStorage.setItem(storageKey(), String(y));
}

function loadScrollPosition() {
  const raw = localStorage.getItem(storageKey());
  if (raw === null) return null;

  const y = Number(raw);
  return Number.isFinite(y) ? y : null;
}

function restoreScrollOnLoad() {
  const y = loadScrollPosition();
  if (y !== null) {
    window.scrollTo(0, y);
  }
}

function setupScrollEndSaver() {
  let timerId = null;

  window.addEventListener("scroll", () => {
    if (timerId !== null) {
      clearTimeout(timerId);
    }

    timerId = setTimeout(() => {
      saveScrollPosition(window.scrollY);
      timerId = null;
    }, 150);
  });
}

function isRangeInsideElement(range, containerEl) {
  const node = range.commonAncestorContainer;
  const ancestor =
    node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
  return !!ancestor && containerEl.contains(ancestor);
}

function clearSelection() {
  const sel = window.getSelection();
  if (sel) sel.removeAllRanges();
}

function addRemoveOnClick(highlightSpan) {
  highlightSpan.addEventListener("click", (e) => {
    e.preventDefault();

    const parent = highlightSpan.parentNode;
    if (!parent) return;

    const textNode = document.createTextNode(highlightSpan.textContent);
    parent.replaceChild(textNode, highlightSpan);
  });
}

function setupHighlighting(contentEl) {
  contentEl.addEventListener("mouseup", () => {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) return;

    const range = selection.getRangeAt(0);

    if (range.collapsed) return;
    if (!isRangeInsideElement(range, contentEl)) return;

    const span = document.createElement("span");
    span.classList.add("highlight");

    try {
      range.surroundContents(span);
      addRemoveOnClick(span);
      clearSelection();
    } catch (err) {
      // ignore invalid ranges
    }
  });
}

function setupDownload(downloadBtn, rootElForHighlights = document) {
  downloadBtn.addEventListener("click", () => {
    const highlighted = Array.from(
      rootElForHighlights.querySelectorAll(".highlight")
    );

    const texts = highlighted.map((el) => el.textContent);

    const json = JSON.stringify(texts, null, 2);
    const encoded = encodeURIComponent(json);

    const a = document.createElement("a");
    a.setAttribute(
      "href",
      `data:application/json;charset=utf-8,${encoded}`
    );
    a.setAttribute("download", "restaurant-highlights.json");

    document.body.appendChild(a);
    a.click();
    a.remove();
  });
}

document.addEventListener("DOMContentLoaded", () => {
  restoreScrollOnLoad();
  setupScrollEndSaver();

  const contentEl = document.querySelector("#restaurant-content");
  if (contentEl) {
    setupHighlighting(contentEl);
  }

  const downloadBtn = document.querySelector("#download-btn");
  if (downloadBtn) {
    setupDownload(downloadBtn, contentEl || document);
  }
});