/**
 * Curricula - Main JavaScript
 */

document.addEventListener("DOMContentLoaded", function () {
  // Initialize tooltips
  const tooltipTriggerList = document.querySelectorAll(
    '[data-bs-toggle="tooltip"]'
  );
  tooltipTriggerList.forEach(function (tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Initialize popovers
  const popoverTriggerList = document.querySelectorAll(
    '[data-bs-toggle="popover"]'
  );
  popoverTriggerList.forEach(function (popoverTriggerEl) {
    new bootstrap.Popover(popoverTriggerEl);
  });

  // Auto-hide alerts
  const alerts = document.querySelectorAll(".alert-dismissible");
  alerts.forEach(function (alert) {
    setTimeout(function () {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      bsAlert.close();
    }, 5000);
  });

  // Confirm delete actions
  document.querySelectorAll("[data-confirm]").forEach(function (element) {
    element.addEventListener("click", function (e) {
      if (!confirm(this.dataset.confirm || "Сигурни ли сте?")) {
        e.preventDefault();
      }
    });
  });

  // Form validation styling
  const forms = document.querySelectorAll(".needs-validation");
  forms.forEach(function (form) {
    form.addEventListener(
      "submit",
      function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add("was-validated");
      },
      false
    );
  });
});

/**
 * Schedule generation progress
 */
function initScheduleGeneration(url, csrfToken) {
  const progressBar = document.getElementById("generation-progress");
  const statusText = document.getElementById("generation-status");
  const resultContainer = document.getElementById("generation-result");

  if (!progressBar || !statusText) return;

  fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-Token": csrfToken,
    },
  })
    .then((response) => response.json())
    .then((data) => {
      progressBar.style.width = "100%";
      progressBar.classList.remove("progress-bar-animated");

      if (data.success) {
        progressBar.classList.remove("bg-info");
        progressBar.classList.add("bg-success");
        statusText.textContent = "Генерирането завърши успешно!";

        if (resultContainer && data.variants) {
          displayVariants(resultContainer, data.variants);
        }
      } else {
        progressBar.classList.remove("bg-info");
        progressBar.classList.add("bg-danger");
        statusText.textContent =
          "Грешка: " + (data.message || "Неизвестна грешка");
      }
    })
    .catch((error) => {
      progressBar.classList.remove("bg-info");
      progressBar.classList.add("bg-danger");
      statusText.textContent = "Грешка при генерирането: " + error.message;
    });
}

/**
 * Display schedule variants
 */
function displayVariants(container, variants) {
  let html = '<div class="list-group">';

  variants.forEach(function (variant, index) {
    html += `
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Вариант ${index + 1}</strong>
                        <br>
                        <small class="text-muted">
                            Фитнес: ${variant.fitness.toFixed(4)} | 
                            Конфликти: ${variant.conflicts}
                        </small>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" onclick="previewVariant(${
                          variant.id
                        })">
                            <i class="bi bi-eye"></i> Преглед
                        </button>
                        <button class="btn btn-sm btn-success" onclick="selectVariant(${
                          variant.id
                        })">
                            <i class="bi bi-check-circle"></i> Избор
                        </button>
                    </div>
                </div>
            </div>
        `;
  });

  html += "</div>";
  container.innerHTML = html;
}

/**
 * Preview a schedule variant
 */
function previewVariant(variantId) {
  window.open(
    "/admin/schedule/preview/" + variantId,
    "_blank",
    "width=1000,height=700"
  );
}

/**
 * Select a schedule variant
 */
function selectVariant(variantId) {
  if (!confirm("Сигурни ли сте, че искате да изберете този вариант?")) {
    return;
  }

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

  fetch("/admin/schedule/select", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-Token": csrfToken,
    },
    body: JSON.stringify({ variant_id: variantId }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Вариантът е избран успешно!");
        window.location.reload();
      } else {
        alert("Грешка: " + (data.message || "Неизвестна грешка"));
      }
    })
    .catch((error) => {
      alert("Грешка: " + error.message);
    });
}

/**
 * Toggle schedule view (table/calendar)
 */
function toggleScheduleView(view) {
  const tableView = document.getElementById("schedule-table-view");
  const calendarView = document.getElementById("schedule-calendar-view");

  if (view === "table") {
    tableView?.classList.remove("d-none");
    calendarView?.classList.add("d-none");
  } else {
    tableView?.classList.add("d-none");
    calendarView?.classList.remove("d-none");
  }

  // Update button states
  document.querySelectorAll("[data-view]").forEach((btn) => {
    btn.classList.toggle("active", btn.dataset.view === view);
  });
}

/**
 * Print schedule
 */
function printSchedule() {
  window.print();
}

/**
 * Export schedule to CSV
 */
function exportScheduleCSV(scheduleId) {
  window.location.href = "/api/schedule/export/csv/" + scheduleId;
}

/**
 * Export schedule to PDF
 */
function exportSchedulePDF(scheduleId) {
  window.location.href = "/api/schedule/export/pdf/" + scheduleId;
}
