const modal = document.getElementById("productModal");
let deleteUrl = "";
let imageToDelete = null;

function openModal(data = null) {
  if (!modal) return;

  const deleteSection = document.getElementById("deleteSection");
  const imgSection = document.getElementById("existingImagesSection");
  const imgTable = document.getElementById("imgListTable");
  const uploadLabel = document.getElementById("uploadLabel");

  modal.style.display = "block";
  if (imgTable) imgTable.innerHTML = "";

  if (data) {
    document.getElementById("modalTitle").innerText =
      "Modifica Prodotto #" + data.Prodotto_ID;
    document.getElementById("f_id").value = data.Prodotto_ID;
    document.getElementById("f_desc").value = data.Descrizione;
    document.getElementById("f_cat").value = data.Categoria;
    document.getElementById("f_prezzo").value = data.Prezzo;
    document.getElementById("f_qty").value = data.Quantita_magazzino;

    deleteUrl = "elimina.php?id=" + data.Prodotto_ID;
    if (deleteSection) deleteSection.style.display = "block";
    if (imgSection) imgSection.style.display = "block";
    if (uploadLabel) uploadLabel.innerText = "Carica un'altra immagine";

    if (data.immagini && data.immagini.length > 0) {
      data.immagini.forEach((img) => {
        addImageRow(img.Nome_file, img.Principale == 1, data.Prodotto_ID);
      });
    }
  } else {
    document.getElementById("modalTitle").innerText = "Aggiungi Prodotto";
    document.getElementById("f_id").value = "";
    document.querySelector("#productModal form").reset();
    if (deleteSection) deleteSection.style.display = "none";
    if (imgSection) imgSection.style.display = "none";
    uploadLabel.innerText = "Immagine Principale";
  }
}

// FIX 1: Use Nome_file as row ID (sanitized), show × button correctly
function addImageRow(nomeFile, isMain, prodottoId) {
  const imgTable = document.getElementById("imgListTable");
  const safeId = nomeFile.replace(/[^a-zA-Z0-9]/g, "_");
  const row = `
        <tr class="${isMain ? "is-main" : ""}" id="img-row-${safeId}">
            <td width="50">
                <img src="../${nomeFile}" style="width:40px; height:40px; object-fit:cover; border-radius:6px;">
            </td>
            <td>
                <div style="font-size:0.75rem; font-weight:600;">${nomeFile.split("/").pop()}</div>
                ${isMain ? '<span class="main-label" style="font-size:0.65rem; color:var(--success); font-weight:700;">PRINCIPALE</span>' : '<span class="main-label"></span>'}
            </td>
            <td align="right">
                <div style="display:flex; align-items:center; gap:10px; justify-content: flex-end;">
                    <label class="switch">
                        <input type="checkbox" ${isMain ? "checked" : ""}
                               onclick="changeMainImage('${nomeFile}', ${prodottoId})">
                        <span class="slider"></span>
                    </label>
                    <button type="button" class="btn-delete-small"
                            onclick="deleteSingleImage('${nomeFile}')">
                        &times;
                    </button>
                </div>
            </td>
        </tr>`;
  imgTable.innerHTML += row;
}

function closeModal() {
  if (modal) modal.style.display = "none";
}

window.onclick = function (event) {
  if (event.target == modal) closeModal();
};

function changeMainImage(imgName, productId) {
  let mainImgInput = document.getElementById("f_main_img");
  if (!mainImgInput) {
    mainImgInput = document.createElement("input");
    mainImgInput.type = "hidden";
    mainImgInput.name = "nuova_immagine_principale";
    mainImgInput.id = "f_main_img";
    document.querySelector("#productModal form").appendChild(mainImgInput);
  }

  mainImgInput.value = imgName;

  const table = document.getElementById("imgListTable");
  const rows = table.querySelectorAll("tr");

  rows.forEach((row) => {
    const checkbox = row.querySelector('input[type="checkbox"]');
    const mainLabel = row.querySelector(".main-label");
    if (checkbox.getAttribute("onclick").includes(`'${imgName}'`)) {
      row.classList.add("is-main");
      checkbox.checked = true;
      if (mainLabel)
        mainLabel.innerHTML =
          '<span style="font-size:0.65rem; color:var(--success); font-weight:700;">PRINCIPALE</span>';
    } else {
      row.classList.remove("is-main");
      checkbox.checked = false;
      if (mainLabel) mainLabel.innerHTML = "";
    }
  });
}

function showConfirmPopup() {
  const confirmPopup = document.getElementById("confirmPopup");
  const finalDeleteLink = document.getElementById("finalDeleteLink");
  if (confirmPopup && finalDeleteLink) {
    finalDeleteLink.href = deleteUrl;
    confirmPopup.style.display = "flex";
  }
}

function closeConfirmPopup() {
  const confirmPopup = document.getElementById("confirmPopup");
  if (confirmPopup) confirmPopup.style.display = "none";
}

// FIX 1: Only takes fileName (no imgId since DB has no Immagine_ID)
function deleteSingleImage(fileName) {
  imageToDelete = { name: fileName };
  const popup = document.getElementById("confirmImagePopup");
  if (popup) popup.style.display = "flex";
}

function closeImageConfirm() {
  const popup = document.getElementById("confirmImagePopup");
  if (popup) popup.style.display = "none";
  imageToDelete = null;
}

document.addEventListener("DOMContentLoaded", function () {
  const btnConfirmImgDelete = document.getElementById("btnConfirmImgDelete");

  if (btnConfirmImgDelete) {
    btnConfirmImgDelete.onclick = function () {
      if (!imageToDelete) return;

      const formData = new FormData();
      formData.append("elimina_immagine", "1");
      formData.append("nome_file", imageToDelete.name);

      fetch("/admin/gestionale", {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          if (response.status === 401) throw new Error("session_expired");
          if (!response.ok) throw new Error("server_error_" + response.status);
          return response.json();
        })
        .then((data) => {
          if (!data.success) throw new Error("delete_failed");

          // FIX 2: Remove row immediately from UI
          const safeId = imageToDelete.name.replace(/[^a-zA-Z0-9]/g, "_");
          const row = document.getElementById(`img-row-${safeId}`);
          const wasMain = row && row.classList.contains("is-main");
          if (row) row.remove();

          // FIX 3: If deleted image was main, auto-promote first remaining image
          if (wasMain) {
            const table = document.getElementById("imgListTable");
            const firstRow = table.querySelector("tr");
            if (firstRow) {
              const checkbox = firstRow.querySelector('input[type="checkbox"]');
              if (checkbox) {
                // Extract filename from onclick attribute
                const onclickVal = checkbox.getAttribute("onclick");
                const match = onclickVal.match(/'([^']+)'/);
                if (match) {
                  const newMainName = match[1];
                  changeMainImage(
                    newMainName,
                    document.getElementById("f_id").value,
                  );

                  // Also tell the server immediately
                  const fd2 = new FormData();
                  fd2.append("elimina_immagine", "1");
                  fd2.append("nome_file", "__noop__"); // dummy, just to set main below
                  // Actually send a separate request to update main image
                  const fd3 = new FormData();
                  fd3.append("salva_principale", "1");
                  fd3.append(
                    "prodotto_id",
                    document.getElementById("f_id").value,
                  );
                  fd3.append("nome_file", newMainName);
                  fetch("/admin/gestionale", { method: "POST", body: fd3 });
                }
              }
            }
          }

          closeImageConfirm();
        })
        .catch((err) => {
          closeImageConfirm();
          if (err.message === "session_expired") {
            alert("Sessione scaduta. Verrai reindirizzato al login.");
            window.location.href = "../index.php";
          } else {
            alert("Errore durante l'eliminazione: " + err.message);
          }
        });
    };
  }
});
