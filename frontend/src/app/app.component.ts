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
  isAuthenticated: boolean = false;
  currentRole: 'egresado' | 'empresa' | 'admin' | null = null;
  currentUsername: string = '';
  loginError: string = '';
  chartInstances: { [key: string]: Chart } = {};
  isLightTheme: boolean = false;
  isSidebarOpen: boolean = false;
  profilePhotoUrl: string | null = null;
  hasCompletedSurvey: boolean = false;
  inegiResults: any[] = [];
  isSearchingInegi: boolean = false;
  inegiError: string = '';
  selectedEmpresa: any = {
    Nombre: 'Tepic, Nayarit (Vista General)',
    Latitud: '21.5045',
    Longitud: '-104.8946',
    Municipio: 'Tepic',
    Entidad: 'Nayarit'
  };
  mapaUrlSeguro: SafeResourceUrl | null = null;

  constructor(private sanitizer: DomSanitizer) {
    const savedPhoto = localStorage.getItem('profilePhotoUrl');
    if (savedPhoto) this.profilePhotoUrl = savedPhoto;

    const savedSurvey = localStorage.getItem('hasCompletedSurvey');
    if (savedSurvey) this.hasCompletedSurvey = savedSurvey === 'true';

    const savedTheme = localStorage.getItem('isLightTheme');
    if (savedTheme) {
      this.isLightTheme = JSON.parse(savedTheme);
      this.applyTheme();
    }
  }

  ngAfterViewInit() {
    this.verMapa(this.selectedEmpresa);
    // Retraso seguro para la renderización inicial
    setTimeout(() => {
      this.renderCharts();
    }, 100);
  }

  switchView(view: string) {
    if (!this.canAccessView(view)) return;
    this.currentView = view;
    this.isSidebarOpen = false;
    
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

  login(username: string, password: string) {
    const normalizedUser = username.trim().toLowerCase();
    const users = [
      { username: 'egresado', password: '1234', role: 'egresado' as const },
      { username: 'empresa', password: '1234', role: 'empresa' as const },
      { username: 'admin', password: '1234', role: 'admin' as const }
    ];

    const foundUser = users.find(u => u.username === normalizedUser && u.password === password);
    if (!foundUser) {
      this.loginError = 'Credenciales incorrectas. Prueba con egresado/empresa/admin y clave 1234.';
      return;
    }

    this.isAuthenticated = true;
    this.currentRole = foundUser.role;
    this.currentUsername = foundUser.username;
    this.loginError = '';

    const defaultView = this.getDefaultViewByRole(foundUser.role);
    this.switchView(defaultView);
  }

  logout() {
    this.isAuthenticated = false;
    this.currentRole = null;
    this.currentUsername = '';
    this.currentView = 'dashboard';
    this.isSidebarOpen = false;
    this.loginError = '';
  }

  canAccessView(view: string): boolean {
    if (!this.currentRole) return false;
    if (this.currentRole === 'egresado') return ['profile', 'empresas'].includes(view);
    if (this.currentRole === 'empresa') return ['dashboard', 'empresas'].includes(view);
    if (this.currentRole === 'admin') return ['dashboard', 'empresas', 'analitica'].includes(view);
    return false;
  }

  getRoleLabel(): string {
    if (this.currentRole === 'egresado') return 'Egresado';
    if (this.currentRole === 'empresa') return 'Empresa';
    if (this.currentRole === 'admin') return 'Administrador';
    return '';
  }

  private getDefaultViewByRole(role: 'egresado' | 'empresa' | 'admin'): string {
    if (role === 'egresado') return 'profile';
    if (role === 'empresa') return 'dashboard';
    return 'analitica';
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
    if (confirm('Deseas enviar tus respuestas de la evaluacion? Una vez enviada, no podras realizarla de nuevo.')) {
      this.hasCompletedSurvey = true;
      localStorage.setItem('hasCompletedSurvey', 'true');
    }
  }

  toggleTheme() {
    this.isLightTheme = !this.isLightTheme;
    localStorage.setItem('isLightTheme', JSON.stringify(this.isLightTheme));
    this.applyTheme();
  }

  private applyTheme() {
    if (this.isLightTheme) {
      document.body.parentElement?.classList.add('light-theme');
    } else {
      document.body.parentElement?.classList.remove('light-theme');
    }
  }

  toggleSidebar() {
    this.isSidebarOpen = !this.isSidebarOpen;
  }

  private renderCharts() {
    if (this.currentView === 'dashboard') {
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

  verMapa(empresa: any) {
    this.selectedEmpresa = empresa;

    const lat = empresa.Latitud || '21.5095';
    const lon = empresa.Longitud || '-104.8956';
    const mapUrl = `https://maps.google.com/maps?q=${lat},${lon}&z=15&output=embed`;
    this.mapaUrlSeguro = this.sanitizer.bypassSecurityTrustResourceUrl(mapUrl);
  }

  trazarRutaEnMapa() {
    if (!this.selectedEmpresa || this.selectedEmpresa.Nombre === 'Tepic, Nayarit (Vista General)') return;

    const destLat = this.selectedEmpresa.Latitud || '21.5095';
    const destLon = this.selectedEmpresa.Longitud || '-104.8956';
    const originLat = '21.4880';
    const originLon = '-104.8900';
    const mapUrl = `https://maps.google.com/maps?saddr=${originLat},${originLon}&daddr=${destLat},${destLon}&output=embed`;
    this.mapaUrlSeguro = this.sanitizer.bypassSecurityTrustResourceUrl(mapUrl);
  }

  registrarEmpresa() {
    alert('Modulo de Registro de Empresa en desarrollo.');
  }

  async buscarEmpresasINEGI(keyword: string) {
    if (!keyword.trim()) {
      this.inegiError = 'Por favor, ingresa una palabra clave o carrera.';
      return;
    }

    this.isSearchingInegi = true;
    this.inegiResults = [];
    this.inegiError = '';

    const TOKEN = 'TU_TOKEN_AQUI';
    const LAT = '21.50';
    const LON = '-104.89';
    const RADIO = '5000';
    const URL = `https://www.inegi.org.mx/app/api/denue/v1/consulta/Buscar/${keyword}/${LAT},${LON}/${RADIO}/${TOKEN}`;

    try {
      let data: any = null;

      try {
        const response = await fetch(URL);
        if (response.ok) {
          data = await response.json();
        } else {
          throw new Error('Token invalido o error de CORS');
        }
      } catch {
        await new Promise(r => setTimeout(r, 900));
        data = this.generarMockInegi(keyword);
      }

      if (data && Array.isArray(data)) {
        this.inegiResults = data.filter(empresa => empresa.Estrato !== '0 a 5 personas');
        if (this.inegiResults.length > 0) {
          this.verMapa(this.inegiResults[0]);
        } else {
          this.inegiError = 'No se encontraron empresas medianas/grandes en el radio especificado.';
        }
      } else {
        this.inegiError = 'La API de INEGI no devolvio resultados validos.';
      }
    } catch {
      this.inegiError = 'Error interno al procesar la busqueda.';
    } finally {
      this.isSearchingInegi = false;
    }
  }

  private generarMockInegi(keyword: string): any[] {
    const k = keyword.toLowerCase();
    if (k.includes('software') || k.includes('computo') || k.includes('sistema')) {
      return [
        { Nombre: 'TechSolutions del Pacifico S.A.', Clase_actividad: 'Desarrollo de Software', Estrato: '11 a 50 personas', Municipio: 'Tepic', Entidad: 'Nayarit', Latitud: '21.5120', Longitud: '-104.8900' },
        { Nombre: 'DevCore Consultores', Clase_actividad: 'Consultoria IT', Estrato: '6 a 10 personas', Municipio: 'Tepic', Entidad: 'Nayarit', Latitud: '21.4980', Longitud: '-104.9010' },
        { Nombre: 'Freelance', Clase_actividad: 'Desarrollo', Estrato: '0 a 5 personas', Municipio: 'Tepic', Entidad: 'Nayarit', Latitud: '21.5050', Longitud: '-104.8950' },
        { Nombre: 'Sistemas Integrales de la Costa', Clase_actividad: 'Redes y Telecomunicaciones', Estrato: '31 a 50 personas', Municipio: 'San Blas', Entidad: 'Nayarit', Latitud: '21.5300', Longitud: '-105.2800' }
      ];
    }

    if (k.includes('arquitectura') || k.includes('constru')) {
      return [
        { Nombre: 'Constructora del Valle', Clase_actividad: 'Servicios de Arquitectura', Estrato: '51 a 100 personas', Municipio: 'Bahia de Banderas', Entidad: 'Nayarit', Latitud: '20.7850', Longitud: '-105.2850' },
        { Nombre: 'Diseno Estructural Nayarita', Clase_actividad: 'Supervision de Obra', Estrato: '11 a 50 personas', Municipio: 'Tepic', Entidad: 'Nayarit', Latitud: '21.5010', Longitud: '-104.8800' }
      ];
    }

    return [
      { Nombre: `Empresa Comercializadora de ${keyword}`, Clase_actividad: 'Comercio al por mayor y menor', Estrato: '51 a 250 personas', Municipio: 'Tepic', Entidad: 'Nayarit', Latitud: '21.5090', Longitud: '-104.8960' },
      { Nombre: `Grupo Industrial ${keyword}`, Clase_actividad: 'Servicios de manufactura y distribucion', Estrato: '11 a 50 personas', Municipio: 'Xalisco', Entidad: 'Nayarit', Latitud: '21.4500', Longitud: '-104.9000' }
    ];
  }

  descargarPDF() {
    window.print();
  }
}
