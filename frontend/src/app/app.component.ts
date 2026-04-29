import { Component, AfterViewInit, ViewEncapsulation } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet } from '@angular/router';
import Chart from 'chart.js/auto';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [CommonModule, RouterOutlet],
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css'],
  encapsulation: ViewEncapsulation.None
})
export class AppComponent implements AfterViewInit {
  currentView: string = 'dashboard';
  chartInstances: { [key: string]: Chart } = {};

  ngAfterViewInit() {
    // Retraso seguro para la renderización inicial
    setTimeout(() => {
      this.renderCharts();
    }, 100);
  }

  switchView(view: string) {
    this.currentView = view;
    
    // Destruir gráficos anteriores para evitar fugas de memoria y errores de canvas
    for (const key in this.chartInstances) {
      if (this.chartInstances[key]) {
        this.chartInstances[key].destroy();
      }
    }
    this.chartInstances = {};

    // Esperar a que Angular actualice el DOM con los ngIf antes de dibujar
    setTimeout(() => {
      this.renderCharts();
    }, 150);
  }

  private renderCharts() {
    if (this.currentView === 'dashboard') {
      this.initHistogramChart();
      this.initRadarChart('radarChartDashboard');
    } else if (this.currentView === 'profile') {
      this.initRadarChart('radarChartProfile');
    } else if (this.currentView === 'analitica') {
      this.initDemandedSkillsChart();
      this.initPlacementChart();
    }
  }

  private initHistogramChart() {
    const ctx = document.getElementById('histogramChart') as HTMLCanvasElement;
    if (!ctx) return;

    this.chartInstances['histogram'] = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Psicométricas', 'Cognitivas', 'Técnicas', 'Proyectivas'],
        datasets: [{
          label: 'Puntaje Promedio',
          data: [85, 78, 92, 88],
          backgroundColor: [
            'rgba(37, 99, 235, 0.8)',
            'rgba(16, 185, 129, 0.8)',
            'rgba(245, 158, 11, 0.8)',
            'rgba(139, 92, 246, 0.8)'
          ],
          borderRadius: 6,
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            grid: { color: 'rgba(255, 255, 255, 0.1)' },
            ticks: { color: '#94a3b8' }
          },
          x: {
            grid: { display: false },
            ticks: { color: '#94a3b8' }
          }
        }
      }
    });
  }

  private initRadarChart(canvasId: string) {
    const ctx = document.getElementById(canvasId) as HTMLCanvasElement;
    if (!ctx) return;

    this.chartInstances[canvasId] = new Chart(ctx, {
      type: 'radar',
      data: {
        labels: ['Liderazgo', 'Lógica', 'Desarrollo Web', 'Resolución', 'Trabajo en Equipo', 'Bases de Datos'],
        datasets: [
          {
            label: 'Perfil Ideal (Referencia)',
            data: [80, 90, 85, 80, 90, 80],
            backgroundColor: 'rgba(16, 185, 129, 0.2)',
            borderColor: 'rgba(16, 185, 129, 1)',
            pointBackgroundColor: 'rgba(16, 185, 129, 1)',
            borderWidth: 2
          },
          {
            label: 'Perfil Real (Egresado)',
            data: [75, 85, 95, 85, 85, 75],
            backgroundColor: 'rgba(37, 99, 235, 0.2)',
            borderColor: 'rgba(37, 99, 235, 1)',
            pointBackgroundColor: 'rgba(37, 99, 235, 1)',
            borderWidth: 2
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          r: {
            angleLines: { color: 'rgba(255, 255, 255, 0.1)' },
            grid: { color: 'rgba(255, 255, 255, 0.1)' },
            pointLabels: {
              color: '#94a3b8',
              font: { family: "'Plus Jakarta Sans', sans-serif" }
            },
            ticks: { display: false },
            max: 100
          }
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              color: '#94a3b8',
              usePointStyle: true,
              font: { family: "'Plus Jakarta Sans', sans-serif" }
            }
          }
        }
      }
    });
  }

  private initDemandedSkillsChart() {
    const ctx = document.getElementById('skillsChart') as HTMLCanvasElement;
    if (!ctx) return;

    this.chartInstances['skills'] = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Inglés Avanzado', 'Angular/React', 'PostgreSQL', 'Metodologías Ágiles', 'Liderazgo'],
        datasets: [{
          label: 'Frecuencia en Vacantes (%)',
          data: [92, 85, 78, 65, 60],
          backgroundColor: 'rgba(37, 99, 235, 0.8)',
          borderRadius: 6
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: {
            beginAtZero: true,
            max: 100,
            grid: { color: 'rgba(255, 255, 255, 0.1)' },
            ticks: { color: '#94a3b8' }
          },
          y: {
            grid: { display: false },
            ticks: { color: '#94a3b8' }
          }
        }
      }
    });
  }

  private initPlacementChart() {
    const ctx = document.getElementById('placementChart') as HTMLCanvasElement;
    if (!ctx) return;

    this.chartInstances['placement'] = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Contratados', 'En Proceso', 'Disponibles'],
        datasets: [{
          data: [65, 15, 20],
          backgroundColor: [
            'rgba(16, 185, 129, 0.8)',
            'rgba(245, 158, 11, 0.8)',
            'rgba(37, 99, 235, 0.8)'
          ],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: { color: '#94a3b8' }
          }
        }
      }
    });
  }
}
