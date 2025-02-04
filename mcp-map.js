document.addEventListener("DOMContentLoaded", function () {
  var map = L.map("map").setView([5.841062173729498, -55.043227522267586], 8);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: "© OpenStreetMap",
  }).addTo(map);

  var markers = [];
  var companyListContainer = document.getElementById("mcp-company-items");
  var searchInput = document.getElementById("mcp-search");
  var districtFilter = document.getElementById("mcp-district-filter");
  var sectorFilter = document.getElementById("mcp-sector-filter");

  // Populate district & business sector dropdowns
  var districts = new Set();
  var sectors = new Set();

  if (typeof mcpCompanies !== "undefined" && Array.isArray(mcpCompanies)) {
    mcpCompanies.forEach(function (company) {
      if (company.district) districts.add(company.district);
      if (company.business_sector) sectors.add(company.business_sector);
    });

    districts.forEach((district) => {
      var option = document.createElement("option");
      option.value = district;
      option.textContent = district;
      districtFilter.appendChild(option);
    });

    sectors.forEach((sector) => {
      var option = document.createElement("option");
      option.value = sector;
      option.textContent = sector;
      sectorFilter.appendChild(option);
    });
  }

  function updateCompanyList() {
    companyListContainer.innerHTML = "";
    markers.forEach((marker) => map.removeLayer(marker));
    markers = [];

    var searchValue = searchInput.value.toLowerCase();
    var selectedDistrict = districtFilter.value;
    var selectedSector = sectorFilter.value;

    mcpCompanies.forEach(function (company) {
      if (
        (searchValue === "" ||
          company.title.toLowerCase().includes(searchValue)) &&
        (selectedDistrict === "" || company.district === selectedDistrict) &&
        (selectedSector === "" || company.business_sector === selectedSector)
      ) {
        var listItem = document.createElement("li");
        listItem.style.padding = "10px";
        listItem.style.borderBottom = "1px solid #ddd";
        listItem.style.cursor = "pointer";

        listItem.innerHTML = `<strong>${company.title}</strong><br>
            ${company.phone ? `📞 ${company.phone}` : ""}
          `;

        var popupContent = `
            <strong>${company.title}</strong><br>
            ${
              company.phone
                ? `<strong>Phone:</strong> ${company.phone}<br>`
                : ""
            }
            ${
              company.email
                ? `<strong>Email:</strong> <a href="mailto:${company.email}">${company.email}</a><br>`
                : ""
            }
            ${
              company.website
                ? `<strong>Website:</strong> <a href="${company.website}" target="_blank">${company.website}</a><br>`
                : ""
            }
            ${
              company.facebook
                ? `<strong>Facebook:</strong> <a href="${company.facebook}" target="_blank">Facebook</a><br>`
                : ""
            }
            ${
              company.instagram
                ? `<strong>Instagram:</strong> <a href="${company.instagram}" target="_blank">Instagram</a><br>`
                : ""
            }
            ${
              company.linkedin
                ? `<strong>LinkedIn:</strong> <a href="${company.linkedin}" target="_blank">LinkedIn</a><br>`
                : ""
            }
            ${
              company.district
                ? `<strong>District:</strong> ${company.district}<br>`
                : ""
            }
            ${
              company.business_sector
                ? `<strong>Business Sector:</strong> ${company.business_sector}<br>`
                : ""
            }
          `;

        var marker = L.marker([company.lat, company.lng])
          .addTo(map)
          .bindPopup(popupContent);

        markers.push(marker);

        listItem.addEventListener("click", function () {
          map.setView([company.lat, company.lng], 14);
          marker.openPopup();
        });

        companyListContainer.appendChild(listItem);
      }
    });
  }

  searchInput.addEventListener("input", updateCompanyList);
  districtFilter.addEventListener("change", updateCompanyList);
  sectorFilter.addEventListener("change", updateCompanyList);

  updateCompanyList();
});

console.log(mcpCompanies);
