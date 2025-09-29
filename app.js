new Vue({
  el: "#app",
  data: {
    system: {
      os: "",
      cpu: { model: "", usage: 0 },
      ram: { total: 0, used: 0 },
      gpu: { model: "", usage: 0 },
      disk: { type: "", total: 0, used: 0 }
    }
  },
  mounted() {
    this.fetchData();
    setInterval(this.fetchData, 10000); // refresh tiap 5 detik
  },
  methods: {
    fetchData() {
      fetch("http://localhost:1234/system-info.php")
        .then(res => res.json())
        .then(data => {
          this.system = data;
          this.renderGauges();
        })
        .catch(err => console.error(err));
    },
    renderGauges() {
      this.renderGauge("cpuChart", this.system.cpu.usage,"rgba(14, 237, 6, 0.13)");
      this.renderGauge("ramChart", (this.system.ram.used / this.system.ram.total) * 100,"rgba(222, 237, 7, 0.13)");
      this.renderGauge("gpuChart", this.system.gpu.usage,"rgba(255, 5, 5, 0.13)");
      this.renderGauge("diskChart", (this.system.disk.used / this.system.disk.total) * 100,"rgba(60, 9, 190, 0.13)");
    },
    renderGauge(id, value,warna) {
      if (Chart.getChart(id)) Chart.getChart(id).destroy();

      if (warna==null || warna===undefined){
        warna = "rgba(234, 0, 0, 0.27)"
      }

      new Chart(document.getElementById(id), {
        type: "doughnut",
        data: {
          datasets: [{
            data: [value, 100 - value],
            backgroundColor: [warna, "#e5e7eb"],
            borderWidth: 0
          }]
        },
        options: {
          cutout: "75%",
          rotation: -90,
          circumference: 180,
          plugins: {
            legend: { display: false },
            tooltip: { enabled: true },
            centerText: {
              display: true,
              text: value.toFixed(0) + "%"
            }
          }
        },
        plugins: [{
          id: 'centerText',
          beforeDraw(chart) {
            let opts = chart.config.options.plugins.centerText;
            if (opts && opts.display) {
              let ctx = chart.ctx;
              let width = chart.width;
              let height = chart.height;
              ctx.restore();

              let fontSize = (height / 6).toFixed(2);
              ctx.font = fontSize + "px Arial";
              ctx.textBaseline = "middle";
              ctx.fillStyle = "#111827"; // text color

              let text = opts.text;
              let textX = Math.round((width - ctx.measureText(text).width) / 2);
              let textY = height / 1.2;

              ctx.fillText(text, textX, textY);
              ctx.save();
            }
          }
        }]
      });
    }
  }
});
