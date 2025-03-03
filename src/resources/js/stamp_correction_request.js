document.addEventListener("DOMContentLoaded", function () {
    const tabs = document.querySelectorAll(".request-list__tab");
    const pendingTable = document.getElementById("pending-requests");
    const approvedTable = document.getElementById("approved-requests");

    tabs.forEach(tab => {
        tab.addEventListener("click", function () {
            tabs.forEach(t => t.classList.remove("active"));
            this.classList.add("active");

            const target = this.getAttribute("data-target");

            pendingTable.style.display = "none";
            approvedTable.style.display = "none";

            if (target === "pending-requests") {
                pendingTable.style.display = "block";
            } else if (target === "approved-requests") {
                approvedTable.style.display = "block";
            }
        });
    });

    pendingTable.style.display = "block";
    approvedTable.style.display = "none";
});
