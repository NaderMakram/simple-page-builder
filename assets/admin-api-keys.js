document.addEventListener("DOMContentLoaded", () => {
  const copyButtons = document.querySelectorAll(".spb-copy-btn");

  copyButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const targetSelector = button.dataset.target;
      const targetInput = document.querySelector(targetSelector);
      if (!targetInput) return;

      // Try navigator.clipboard first (modern browsers, secure context)
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
          .writeText(targetInput.value)
          .then(() => showCopied(button))
          .catch((err) => {
            console.error("Clipboard API failed:", err);
            fallbackCopy(targetInput, button);
          });
      } else {
        // Fallback for older browsers
        fallbackCopy(targetInput, button);
      }
    });
  });

  // Shows checkmark and reverts after 2s
  function showCopied(button) {
    const originalText = button.textContent;
    button.textContent = "✔ Copied!";
    button.classList.add("spb-copied");
    setTimeout(() => {
      button.textContent = originalText;
      button.classList.remove("spb-copied");
    }, 2000);
  }

  // Fallback copy using execCommand
  function fallbackCopy(input, button) {
    input.select();
    input.setSelectionRange(0, 99999); // for mobile
    try {
      const successful = document.execCommand("copy");
      if (successful) showCopied(button);
      else console.error("Fallback copy failed");
    } catch (err) {
      console.error("Fallback copy error:", err);
    }
    // Deselect text
    window.getSelection().removeAllRanges();
  }
});
