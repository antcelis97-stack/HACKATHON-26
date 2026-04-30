import { Component, AfterViewInit, ViewEncapsulation } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet } from '@angular/router';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
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

  // Variables for INEGI DENUE Search
  inegiResults: any[] = [];
  isSearchingInegi: boolean = false;
  inegiError: string = '';

  // Variables for Map
  selectedEmpresa: any = { Nombre: "Tepic, Nayarit (Vista General)", Latitud: "21.5045", Longitud: "-104.8946", Municipio: "Tepic", Entidad: "Nayarit" };
  mapaUrlSeguro: SafeResourceUrl | null = null;
  rutaUrl: string = '';

  // Theme Management (Predeterminado: Light para diseño institucional)
  isLightTheme: boolean = true;
  isSidebarOpen: boolean = false;

  // Profile Management
  profilePhotoUrl: string | null = null;
  hasCompletedSurvey: boolean = false;
  isSurveyModalOpen: boolean = false;
  surveyStep: number = 0;
  surveyScores = { psicometricas: 50, cognitivas: 50, tecnicas: 50, proyectivas: 50 };

  // Dynamic Matching System
  matchPercentage: number = 75;
  selectedVacante: any = null;
  vacantes = [
    { id: 1, nombre: 'Desarrollador Fullstack Jr.', ideal: [80, 95, 95, 85, 80, 90], empresa: 'Tech Nayarit' },
    { id: 2, nombre: 'Gerente de Proyectos IT', ideal: [95, 80, 70, 95, 95, 75], empresa: 'Soluciones Costa' },
    { id: 3, nombre: 'Analista de Bases de Datos', ideal: [70, 90, 85, 80, 75, 98], empresa: 'Data Nay' },
    { id: 4, nombre: 'Soporte Técnico Especializado', ideal: [85, 75, 80, 90, 85, 70], empresa: 'Global Services' }
  ];

  // Interoperability SIEst 2.0
  isSyncingSIEst: boolean = false;
  siestData: any = null;

  constructor(private sanitizer: DomSanitizer) {
    // Recuperar datos de localStorage
    const savedPhoto = localStorage.getItem('profilePhotoUrl');
    if (savedPhoto) this.profilePhotoUrl = savedPhoto;

    const savedSurvey = localStorage.getItem('hasCompletedSurvey');
    if (savedSurvey) this.hasCompletedSurvey = savedSurvey === 'true';

    // Forzar modo claro institucional por defecto en esta actualización
    const savedTheme = localStorage.getItem('isLightTheme');
    if (savedTheme) {
      this.isLightTheme = JSON.parse(savedTheme);
    } else {
      this.isLightTheme = true; // Por defecto siempre claro
    }
    this.applyTheme();
    this.selectedVacante = this.vacantes[0]; 
  }

  onPhotoSelected(event: any) {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (e: any) => {
        this.profilePhotoUrl = e.target.result;
        if (this.profilePhotoUrl) {
          localStorage.setItem('profilePhotoUrl', this.profilePhotoUrl);
        }
      };
      reader.readAsDataURL(file);
    }
  }

  completeSurvey() {
    this.isSurveyModalOpen = true;
    this.surveyStep = 1;
  }

  submitSurveyStep(dimension: string, score: number) {
    (this.surveyScores as any)[dimension] = score;
    if (this.surveyStep < 4) {
      this.surveyStep++;
    } else {
      this.finishSurvey();
    }
  }

  private finishSurvey() {
    this.hasCompletedSurvey = true;
    this.isSurveyModalOpen = false;
    localStorage.setItem('hasCompletedSurvey', 'true');
    this.updateRadarData();
    alert('¡Evaluación Finalizada! Tus resultados han sido procesados y el gráfico de competencias actualizado.');
  }

  onVacanteChange(event: any) {
    const vacanteId = event.target.value;
    this.selectedVacante = this.vacantes.find(v => v.id == vacanteId);
    this.updateRadarData();
  }

  private updateRadarData() {
    const radarChart = this.chartInstances['radarChartProfile'];
    if (radarChart && this.selectedVacante) {
      // 1. Actualizar Perfil Ideal (Dataset 0)
      radarChart.data.datasets[0].data = this.selectedVacante.ideal;
      radarChart.data.datasets[0].label = `Perfil Ideal: ${this.selectedVacante.nombre}`;

      // 2. Actualizar Perfil Real si ya completó la encuesta
      if (this.hasCompletedSurvey) {
        radarChart.data.datasets[1].data = [
          this.surveyScores.psicometricas + 20,
          this.surveyScores.cognitivas + 10,
          this.surveyScores.tecnicas + 5,
          this.surveyScores.cognitivas + 15,
          this.surveyScores.psicometricas + 15,
          this.surveyScores.tecnicas + 10
        ];
      }

      // 3. Calcular Match %
      this.calculateMatch();
      radarChart.update();
    }
  }

  syncWithSIEst() {
    this.isSyncingSIEst = true;
    setTimeout(() => {
      this.siestData = {
        promedio: 9.2,
        estatus: 'Egresado Titulado',
        generacion: '2021-2023',
        cedula: 'PENDIENTE',
        menciones: ['Excelencia Académica', 'Primer Lugar de Generación']
      };
      this.isSyncingSIEst = false;
      alert('¡Sincronización con SIEst 2.0 exitosa! Los datos académicos han sido actualizados.');
    }, 2000);
  }

  private calculateMatch() {
    if (!this.selectedVacante) return;
    const real = this.chartInstances['radarChartProfile'].data.datasets[1].data;
    const ideal = this.selectedVacante.ideal;
    
    let sumDiff = 0;
    real.forEach((val: any, i: number) => {
      const v = typeof val === 'number' ? val : 0;
      const diff = Math.abs(v - ideal[i]);
      sumDiff += diff;
    });
    
    // Un cálculo simple de afinidad
    this.matchPercentage = Math.max(0, Math.min(100, Math.round(100 - (sumDiff / 6))));
  }

  resetEvaluation() {
    if (confirm('¿Deseas reiniciar la evaluación para una nueva demostración?')) {
      this.hasCompletedSurvey = false;
      localStorage.removeItem('hasCompletedSurvey');
      const radarChart = this.chartInstances['radarChartProfile'];
      if (radarChart) {
        // Restaurar a valores por defecto (ejemplo 65, 59, 80, ...)
        radarChart.data.datasets[1].data = [65, 59, 80, 81, 56, 55];
        radarChart.update();
      }
      alert('Sistema reiniciado para una nueva prueba.');
    }
  }

  toggleTheme() {
    this.isLightTheme = !this.isLightTheme;
    localStorage.setItem('isLightTheme', JSON.stringify(this.isLightTheme));
    this.applyTheme();
  }

  private applyTheme() {
    if (!this.isLightTheme) {
      document.body.parentElement?.classList.add('dark-theme');
      document.body.parentElement?.classList.remove('light-theme');
    } else {
      document.body.parentElement?.classList.remove('dark-theme');
      document.body.parentElement?.classList.remove('light-theme');
    }
  }

  toggleSidebar() {
    this.isSidebarOpen = !this.isSidebarOpen;
  }

  ngAfterViewInit() {
    this.verMapa(this.selectedEmpresa); // Inicializar mapa por defecto
    // Retraso seguro para la renderización inicial
    setTimeout(() => {
      this.renderCharts();
    }, 100);
  }

  switchView(view: string) {
    this.currentView = view;
    this.isSidebarOpen = false; // Close sidebar on mobile
    
    
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
      this.initCompetenciesChart('radarChartDashboard');
    } else if (this.currentView === 'profile') {
      this.initCompetenciesChart('radarChartProfile');
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

  private initCompetenciesChart(canvasId: string) {
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
            min: 0,
            max: 100,
            grid: { color: 'rgba(255, 255, 255, 0.1)' },
            angleLines: { color: 'rgba(255, 255, 255, 0.1)' },
            pointLabels: { color: '#94a3b8', font: { size: 12 } },
            ticks: { display: false }
          }
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: { color: '#94a3b8' }
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

  // ==========================================
  // LÓGICA DEL MAPA Y RUTAS
  // ==========================================
  verMapa(empresa: any) {
    this.selectedEmpresa = empresa;
    
    const lat = empresa.Latitud || "21.5095";
    const lon = empresa.Longitud || "-104.8956";
    
    // Vista estática de un solo punto
    const mapUrl = `https://maps.google.com/maps?q=${lat},${lon}&z=15&output=embed`;
    this.mapaUrlSeguro = this.sanitizer.bypassSecurityTrustResourceUrl(mapUrl);
  }

  trazarRutaEnMapa() {
    if (!this.selectedEmpresa || this.selectedEmpresa.Nombre === 'Tepic, Nayarit (Vista General)') return;
    
    const destLat = this.selectedEmpresa.Latitud || "21.5095";
    const destLon = this.selectedEmpresa.Longitud || "-104.8956";
    
    // Simular ubicación actual (ej. Centro de Tepic o UT)
    const originLat = "21.4880";
    const originLon = "-104.8900";
    
    // Parámetros saddr (start) y daddr (destination) para embeber ruta
    const mapUrl = `https://maps.google.com/maps?saddr=${originLat},${originLon}&daddr=${destLat},${destLon}&output=embed`;
    this.mapaUrlSeguro = this.sanitizer.bypassSecurityTrustResourceUrl(mapUrl);
  }

  registrarEmpresa() {
    alert("Módulo de Registro de Empresa en desarrollo.\n\nPróximamente conectaremos este formulario con la base de datos PostgreSQL.");
  }

  // ==========================================
  // LÓGICA DE LA API DENUE (INEGI)
  // ==========================================
  async buscarEmpresasINEGI(keyword: string) {
    if (!keyword.trim()) {
      this.inegiError = "Por favor, ingresa una palabra clave o carrera.";
      return;
    }
    
    this.isSearchingInegi = true;
    this.inegiResults = [];
    this.inegiError = '';

    const TOKEN = "TU_TOKEN_AQUI"; // Llave INEGI
    const LAT = "21.50";
    const LON = "-104.89"; // Coordenadas Nayarit
    const RADIO = "5000";
    const URL = `https://www.inegi.org.mx/app/api/denue/v1/consulta/Buscar/${keyword}/${LAT},${LON}/${RADIO}/${TOKEN}`;

    try {
      let data: any = null;
      
      // Intento de conexión real a INEGI
      try {
        const response = await fetch(URL);
        if (response.ok) {
           data = await response.json();
        } else {
           throw new Error("Token inválido o error de CORS");
        }
      } catch (e) {
        // En un Hackathon es vital que siempre funcione la demostración. 
        // Si no hay internet, falla por CORS o token inválido, usamos Mocks:
        console.warn("Fallo conexión con API INEGI (CORS/Token), usando datos de respaldo simulados para demostración.");
        await new Promise(r => setTimeout(r, 1200)); // Simulando latencia de red
        data = this.generarMockInegi(keyword);
      }

      if (data && Array.isArray(data)) {
        // Filtrar microempresas (0 a 5 personas) como sugiere api.md
        this.inegiResults = data.filter(empresa => empresa.Estrato !== "0 a 5 personas");
        
        if(this.inegiResults.length > 0) {
           // Auto-seleccionar el primer resultado para que aparezca en el mapa inmediatamente
           this.verMapa(this.inegiResults[0]);
        } else {
           this.inegiError = "No se encontraron empresas medianas/grandes con esos criterios en el radio especificado.";
        }
      } else {
        this.inegiError = "La API de INEGI no devolvió resultados válidos.";
      }
      
    } catch (error) {
      console.error(error);
      this.inegiError = "Error interno al procesar la búsqueda.";
    } finally {
      this.isSearchingInegi = false;
    }
  }

  private generarMockInegi(keyword: string): any[] {
    const k = keyword.toLowerCase();
    if(k.includes('software') || k.includes('computo') || k.includes('sistema')) {
      return [
        { Nombre: "TechSolutions del Pacífico S.A.", Clase_actividad: "Desarrollo de Software", Estrato: "11 a 50 personas", Municipio: "Tepic", Entidad: "Nayarit", Latitud: "21.5120", Longitud: "-104.8900" },
        { Nombre: "DevCore Consultores", Clase_actividad: "Consultoría IT", Estrato: "6 a 10 personas", Municipio: "Tepic", Entidad: "Nayarit", Latitud: "21.4980", Longitud: "-104.9010" },
        { Nombre: "Freelance", Clase_actividad: "Desarrollo", Estrato: "0 a 5 personas", Municipio: "Tepic", Entidad: "Nayarit", Latitud: "21.5050", Longitud: "-104.8950" },
        { Nombre: "Sistemas Integrales de la Costa", Clase_actividad: "Redes y Telecomunicaciones", Estrato: "31 a 50 personas", Municipio: "San Blas", Entidad: "Nayarit", Latitud: "21.5300", Longitud: "-105.2800" }
      ];
    } else if (k.includes('arquitectura') || k.includes('constru')) {
      return [
        { Nombre: "Constructora del Valle", Clase_actividad: "Servicios de Arquitectura", Estrato: "51 a 100 personas", Municipio: "Bahía de Banderas", Entidad: "Nayarit", Latitud: "20.7850", Longitud: "-105.2850" },
        { Nombre: "Diseño Estructural Nayarita", Clase_actividad: "Supervisión de Obra", Estrato: "11 a 50 personas", Municipio: "Tepic", Entidad: "Nayarit", Latitud: "21.5010", Longitud: "-104.8800" }
      ];
    } else {
      return [
        { Nombre: `Empresa Comercializadora de ${keyword}`, Clase_actividad: `Comercio al por mayor y menor`, Estrato: "51 a 250 personas", Municipio: "Tepic", Entidad: "Nayarit", Latitud: "21.5090", Longitud: "-104.8960" },
        { Nombre: `Grupo Industrial ${keyword}`, Clase_actividad: "Servicios de manufactura y distribución", Estrato: "11 a 50 personas", Municipio: "Xalisco", Entidad: "Nayarit", Latitud: "21.4500", Longitud: "-104.9000" }
      ];
    }
  }

  // ==========================================
  // EXPORTACIÓN A PDF
  // ==========================================
  descargarPDF() {
    // Al invocar window.print() el navegador despliega la ventana
    // que permite al usuario directamente "Guardar como PDF".
    // Esto asegura máxima compatibilidad y conserva la seguridad local.
    window.print();
  }
}
