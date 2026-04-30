import { Component, AfterViewInit, ViewEncapsulation } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet } from '@angular/router';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import Chart from 'chart.js/auto';

type CareerId = 'tic' | 'agrobiotec';
type ScoringMode = 'likert-normalized' | 'sum-points-100' | 'average-likert-100';

interface EvaluationOption {
  id: string;
  text: string;
  points: number;
}

interface EvaluationQuestion {
  id: string;
  dimension: string;
  text: string;
  kind: 'likert' | 'single';
  options: EvaluationOption[];
}

interface EvaluationSection {
  id: 'psicometrica' | 'cognitiva' | 'tecnica' | 'proyectiva';
  title: string;
  description: string;
  scoring: ScoringMode;
  questions: EvaluationQuestion[];
}

interface SurveyResult {
  psicometrica: number;
  cognitiva: number;
  tecnica: number;
  proyectiva: number;
}

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
  /** Por defecto claro, alineado con https://www.utdelacosta.edu.mx/principal (superficies claras + azul). */
  isLightTheme: boolean = true;
  isSidebarOpen: boolean = false;
  profilePhotoUrl: string | null = null;
  /** Nombre del último CV elegido (demo; la subida real irá al backend). */
  cvNombreArchivo: string | null = null;
  hasCompletedSurvey: boolean = false;
  surveyOpen = false;
  selectedCareerId: CareerId = 'tic';
  surveyAnswers: Record<string, number> = {};
  surveyResult: SurveyResult = {
    psicometrica: 85,
    cognitiva: 78,
    tecnica: 95,
    proyectiva: 88
  };
  inegiResults: any[] = [];
  isSearchingInegi: boolean = false;
  inegiError: string = '';
  /** true solo si se usan datos de demostración (p. ej. backend no disponible) */
  inegiUsingMock: boolean = false;
  /**
   * URL mostrada en errores (sin proxy). Con `ng serve` las peticiones van a `/backend-api/...`.
   */
  private readonly backendBolsaUrl = 'http://localhost/HACKATHON-26/backend/inegi-buscar.php';
  selectedEmpresa: any = {
    Nombre: 'Tepic, Nayarit (Vista General)',
    Latitud: '21.5045',
    Longitud: '-104.8946',
    Municipio: 'Tepic',
    Entidad: 'Nayarit'
  };
  mapaUrlSeguro: SafeResourceUrl | null = null;

  /** Panel visible dentro de Analítica (elegido desde el submenú lateral). */
  analiticaPanelId: 'resumen' | 'egresados_carrera' | 'postulaciones_prueba' | 'vacantes_prueba' = 'resumen';

  /** Submenú de reportes bajo «Analítica y Reportes» en el sidebar. */
  reportesMenuOpen = false;

  /** Reporte 1: egresados por carrera (API + PostgreSQL; si falla, datos de prueba). */
  reporteEgresadosCarreraFilas: {
    id_carrera: string;
    carrera: string;
    total_egresados: number;
    con_examenes_completos: number;
  }[] = [];
  reporteEgresadosCarreraLoading = false;
  /** true si la tabla muestra el arreglo de ejemplo (no vino bien la API o la BD está vacía). */
  reporteEgresadosCarreraUsaPrueba = false;

  /** Reporte de prueba: postulaciones por estatus (no conectado a BD aún). */
  readonly reportePruebaPostulaciones: { estatus: string; total: number }[] = [
    { estatus: 'postulado', total: 42 },
    { estatus: 'en_proceso', total: 18 },
    { estatus: 'contratado', total: 11 },
    { estatus: 'rechazado', total: 7 }
  ];

  /** Reporte de prueba: vacantes por empresa (no conectado a BD aún). */
  readonly reportePruebaVacantes: { empresa: string; vacantes_activas: number; postulaciones_totales: number }[] = [
    { empresa: 'TechSolutions del Pacífico S.A.', vacantes_activas: 5, postulaciones_totales: 38 },
    { empresa: 'Constructora del Valle', vacantes_activas: 2, postulaciones_totales: 14 },
    { empresa: 'Logística Costa Norte', vacantes_activas: 3, postulaciones_totales: 22 },
    { empresa: 'Servicios Educativos Integrados', vacantes_activas: 1, postulaciones_totales: 9 }
  ];

  readonly likertOptions: EvaluationOption[] = [
    { id: '1', text: 'Totalmente en desacuerdo', points: 1 },
    { id: '2', text: 'En desacuerdo', points: 2 },
    { id: '3', text: 'Ni de acuerdo ni en desacuerdo', points: 3 },
    { id: '4', text: 'De acuerdo', points: 4 },
    { id: '5', text: 'Totalmente de acuerdo', points: 5 }
  ];

  readonly surveyByCareer: Record<CareerId, EvaluationSection[]> = {
    tic: [
      {
        id: 'psicometrica',
        title: 'Evaluación Psicométrica (Personalidad y Trabajo)',
        description: 'Mide liderazgo, trabajo en equipo y tolerancia a la frustración (Likert 1-5).',
        scoring: 'likert-normalized',
        questions: [
          {
            id: 'tic-psy-1',
            dimension: 'Trabajo en Equipo',
            text: 'Prefiero invertir tiempo explicando un proceso a un compañero que terminarlo yo solo para asegurar la calidad del equipo.',
            kind: 'likert',
            options: this.likertOptions
          },
          {
            id: 'tic-psy-2',
            dimension: 'Tolerancia',
            text: 'Mantengo la concentración y la calma cuando un error de código bloquea un despliegue a pocos minutos de la entrega.',
            kind: 'likert',
            options: this.likertOptions
          },
          {
            id: 'tic-psy-3',
            dimension: 'Adaptabilidad',
            text: 'Me siento cómodo aprendiendo un nuevo lenguaje de programación o framework aunque no sea mi área de especialidad.',
            kind: 'likert',
            options: this.likertOptions
          }
        ]
      },
      {
        id: 'cognitiva',
        title: 'Evaluación Cognitiva (Agilidad Mental)',
        description: 'Razonamiento lógico, patrones y resolución de problemas.',
        scoring: 'sum-points-100',
        questions: [
          {
            id: 'tic-cog-1',
            dimension: 'Lógica',
            text: 'Si A es verdadero y B es falso, ¿cuál es el resultado de (A AND B) OR (NOT B)?',
            kind: 'single',
            options: [
              { id: 'a', text: 'Verdadero', points: 10 },
              { id: 'b', text: 'Falso', points: 0 }
            ]
          },
          {
            id: 'tic-cog-2',
            dimension: 'Patrones',
            text: 'Completa la secuencia: 1, 1, 2, 3, 5, 8, __',
            kind: 'single',
            options: [
              { id: 'a', text: '11', points: 0 },
              { id: 'b', text: '13', points: 10 },
              { id: 'c', text: '15', points: 0 }
            ]
          },
          {
            id: 'tic-cog-3',
            dimension: 'Análisis',
            text: 'Si X tarda el doble que Y y Y tarda 10ms, ¿cuánto tardan ambos secuencialmente?',
            kind: 'single',
            options: [
              { id: 'a', text: '20ms', points: 0 },
              { id: 'b', text: '30ms', points: 10 }
            ]
          }
        ]
      },
      {
        id: 'tecnica',
        title: 'Evaluación Técnica (Conocimientos TIC)',
        description: 'Dominio de herramientas, bases de datos y desarrollo.',
        scoring: 'sum-points-100',
        questions: [
          {
            id: 'tic-tec-1',
            dimension: 'PostgreSQL',
            text: '¿Cuál es la función principal de una Foreign Key en una base relacional?',
            kind: 'single',
            options: [
              { id: 'a', text: 'Aumentar velocidad de consultas', points: 0 },
              { id: 'b', text: 'Garantizar integridad referencial', points: 10 },
              { id: 'c', text: 'Encriptar datos sensibles', points: 0 }
            ]
          },
          {
            id: 'tic-tec-2',
            dimension: 'Angular',
            text: '¿Cuál es el propósito de un Decorator como @Component?',
            kind: 'single',
            options: [
              { id: 'a', text: 'Definir estilos CSS', points: 0 },
              { id: 'b', text: 'Proveer metadatos para Angular', points: 10 },
              { id: 'c', text: 'Compilar TypeScript a JavaScript', points: 0 }
            ]
          },
          {
            id: 'tic-tec-3',
            dimension: 'Seguridad',
            text: '¿Qué práctica es más efectiva para prevenir inyección SQL?',
            kind: 'single',
            options: [
              { id: 'a', text: 'Consultas parametrizadas (Prepared Statements)', points: 10 },
              { id: 'b', text: 'Validación solo en frontend', points: 0 },
              { id: 'c', text: 'Cambiar contraseñas cada mes', points: 0 }
            ]
          }
        ]
      },
      {
        id: 'proyectiva',
        title: 'Evaluación Proyectiva (Juicio Situacional)',
        description: 'Rasgos profundos y estabilidad emocional en entorno laboral.',
        scoring: 'sum-points-100',
        questions: [
          {
            id: 'tic-proj-1',
            dimension: 'Madurez profesional',
            text: 'Cliente critica duramente tu interfaz frente a tus superiores. ¿Cómo reaccionas?',
            kind: 'single',
            options: [
              { id: 'a', text: 'Defiendo mi trabajo y digo que no entiende de diseño técnico.', points: 2 },
              { id: 'b', text: 'Guardo silencio y espero a que mi jefe hable.', points: 5 },
              { id: 'c', text: 'Escucho, tomo nota y propongo reunión técnica para ajustar.', points: 10 }
            ]
          }
        ]
      }
    ],
    agrobiotec: [
      {
        id: 'psicometrica',
        title: 'Evaluación Psicométrica (Comportamiento Laboral)',
        description: 'Mide rigor científico, ética y colaboración (Likert 1-5).',
        scoring: 'average-likert-100',
        questions: [
          {
            id: 'agr-psy-1',
            dimension: 'Rigor',
            text: 'Sigo estrictamente protocolos de bioseguridad incluso con tiempos de entrega urgentes.',
            kind: 'likert',
            options: this.likertOptions
          },
          {
            id: 'agr-psy-2',
            dimension: 'Ética',
            text: 'La transparencia en resultados negativos es tan importante como en positivos.',
            kind: 'likert',
            options: this.likertOptions
          },
          {
            id: 'agr-psy-3',
            dimension: 'Colaboración',
            text: 'Prefiero proyectos interdisciplinarios para optimizar procesos agrícolas.',
            kind: 'likert',
            options: this.likertOptions
          }
        ]
      },
      {
        id: 'cognitiva',
        title: 'Evaluación Cognitiva (Agilidad y Lógica Científica)',
        description: 'Razonamiento numérico aplicado e interpretación de datos.',
        scoring: 'sum-points-100',
        questions: [
          {
            id: 'agr-cog-1',
            dimension: 'Cálculo',
            text: 'Para 500L de solución al 2% desde concentrado al 10%, ¿cuántos litros se requieren?',
            kind: 'single',
            options: [
              { id: 'a', text: '50 litros', points: 0 },
              { id: 'b', text: '100 litros', points: 10 },
              { id: 'c', text: '25 litros', points: 0 }
            ]
          },
          {
            id: 'agr-cog-2',
            dimension: 'Interpretación',
            text: 'Lote A y B alcanzan 85%, pero A en 4 días y B en 8. Conclusión lógica:',
            kind: 'single',
            options: [
              { id: 'a', text: 'Las hormonas aumentan porcentaje final.', points: 2 },
              { id: 'b', text: 'Las hormonas reducen latencia sin alterar viabilidad final.', points: 10 },
              { id: 'c', text: 'El lote B es superior por natural.', points: 0 }
            ]
          }
        ]
      },
      {
        id: 'tecnica',
        title: 'Evaluación Técnica (Conocimientos Específicos)',
        description: 'Biotecnología vegetal, fitopatología y mejora genética.',
        scoring: 'sum-points-100',
        questions: [
          {
            id: 'agr-tec-1',
            dimension: 'Fitopatología',
            text: 'Lesiones necróticas con anillos concéntricos en tomate. ¿Agente y tratamiento?',
            kind: 'single',
            options: [
              { id: 'a', text: 'Phytophthora / fungicidas cúpricos', points: 0 },
              { id: 'b', text: 'Alternaria solani / rotación y control de humedad', points: 10 },
              { id: 'c', text: 'Fusarium / incrementar riego nitrogenado', points: 0 }
            ]
          },
          {
            id: 'agr-tec-2',
            dimension: 'Mejora genética',
            text: 'Propósito principal de Agrobacterium tumefaciens en biotecnología vegetal:',
            kind: 'single',
            options: [
              { id: 'a', text: 'Fertilizante fijador de nitrógeno', points: 0 },
              { id: 'b', text: 'Vector de transferencia de genes foráneos', points: 10 },
              { id: 'c', text: 'Inhibidor de malezas', points: 2 }
            ]
          }
        ]
      },
      {
        id: 'proyectiva',
        title: 'Evaluación Proyectiva (Juicio Situacional)',
        description: 'Estabilidad emocional y toma de decisiones ante crisis.',
        scoring: 'sum-points-100',
        questions: [
          {
            id: 'agr-proj-1',
            dimension: 'Integridad',
            text: 'Detectas error que invalida 3 meses de datos y te sugieren “ajustar”. ¿Qué haces?',
            kind: 'single',
            options: [
              { id: 'a', text: 'Acepto ajustar por única vez.', points: 2 },
              { id: 'b', text: 'Reporto error y propongo contingencia.', points: 10 },
              { id: 'c', text: 'Corrijo lo mío y callo lo demás.', points: 5 }
            ]
          }
        ]
      }
    ]
  };

  constructor(private sanitizer: DomSanitizer) {
    const savedPhoto = localStorage.getItem('profilePhotoUrl');
    if (savedPhoto) this.profilePhotoUrl = savedPhoto;

    const savedSurvey = localStorage.getItem('hasCompletedSurvey');
    if (savedSurvey) this.hasCompletedSurvey = savedSurvey === 'true';
    const savedCareer = localStorage.getItem('selectedCareerId');
    if (savedCareer === 'tic' || savedCareer === 'agrobiotec') {
      this.selectedCareerId = savedCareer;
    }
    const savedSurveyResult = localStorage.getItem('surveyResult');
    if (savedSurveyResult) {
      try {
        const parsed = JSON.parse(savedSurveyResult) as SurveyResult;
        if (
          typeof parsed.psicometrica === 'number' &&
          typeof parsed.cognitiva === 'number' &&
          typeof parsed.tecnica === 'number' &&
          typeof parsed.proyectiva === 'number'
        ) {
          this.surveyResult = parsed;
        }
      } catch {
        // Ignorar errores de parseo y conservar valores por defecto.
      }
    }

    const savedTheme = localStorage.getItem('isLightTheme');
    if (savedTheme !== null) {
      try {
        this.isLightTheme = JSON.parse(savedTheme) === true;
      } catch {
        this.isLightTheme = true;
      }
    }
    this.applyTheme();

    const savedCvNombre = localStorage.getItem('egresadoCvNombre');
    if (savedCvNombre) this.cvNombreArchivo = savedCvNombre;
  }

  ngAfterViewInit() {
    this.verMapa(this.selectedEmpresa);
    // Retraso seguro para la renderización inicial
    setTimeout(() => {
      this.renderCharts();
    }, 100);
  }

  switchView(
    view: string,
    options?: { analiticaPanel?: 'resumen' | 'egresados_carrera' | 'postulaciones_prueba' | 'vacantes_prueba' }
  ) {
    if (!this.canAccessView(view)) return;
    const prevView = this.currentView;
    if (view !== 'analitica') {
      this.reportesMenuOpen = false;
    }
    this.currentView = view;
    this.isSidebarOpen = false;

    if (view === 'analitica' && prevView !== 'analitica') {
      this.analiticaPanelId = options?.analiticaPanel ?? 'resumen';
      this.reportesMenuOpen = true;
    }

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

  onCvSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    const ok = /\.(pdf|doc|docx)$/i.test(file.name);
    if (!ok) {
      alert('Formato no admitido. Usa PDF, DOC o DOCX.');
      input.value = '';
      return;
    }
    this.cvNombreArchivo = file.name;
    localStorage.setItem('egresadoCvNombre', file.name);
    alert(
      `Archivo "${file.name}" listo. (Demo: la subida a Drive/backend se conectará cuando tengas el endpoint de CV activo.)`
    );
    input.value = '';
  }

  completeSurvey() {
    if (this.hasCompletedSurvey) return;
    this.surveyOpen = true;
    this.surveyAnswers = {};
  }

  cancelSurvey(): void {
    this.surveyOpen = false;
    this.surveyAnswers = {};
  }

  selectCareer(careerId: CareerId): void {
    this.selectedCareerId = careerId;
    this.surveyAnswers = {};
    localStorage.setItem('selectedCareerId', careerId);
  }

  getCurrentSections(): EvaluationSection[] {
    return this.surveyByCareer[this.selectedCareerId];
  }

  getAnswer(questionId: string): number | null {
    const value = this.surveyAnswers[questionId];
    return Number.isFinite(value) ? value : null;
  }

  answerQuestion(question: EvaluationQuestion, option: EvaluationOption): void {
    this.surveyAnswers[question.id] = option.points;
  }

  canSubmitSurvey(): boolean {
    const allQuestions = this.getCurrentSections().flatMap((section) => section.questions);
    return allQuestions.every((question) => typeof this.surveyAnswers[question.id] === 'number');
  }

  submitSurvey(): void {
    if (!this.canSubmitSurvey()) {
      alert('Responde todas las preguntas antes de enviar la evaluación.');
      return;
    }
    const result = this.calculateSurveyResult();
    this.surveyResult = result;
    this.hasCompletedSurvey = true;
    this.surveyOpen = false;
    localStorage.setItem('hasCompletedSurvey', 'true');
    localStorage.setItem('surveyResult', JSON.stringify(result));
    localStorage.setItem('selectedCareerId', this.selectedCareerId);
  }

  private calculateSurveyResult(): SurveyResult {
    const sections = this.getCurrentSections();
    const getSectionById = (id: EvaluationSection['id']): EvaluationSection | null =>
      sections.find((section) => section.id === id) ?? null;
    const sectionScore = (section: EvaluationSection): number => {
      const values = section.questions.map((q) => this.surveyAnswers[q.id] ?? 0);
      if (values.length === 0) return 0;

      if (section.scoring === 'likert-normalized') {
        const sum = values.reduce((acc, value) => acc + value, 0);
        const max = section.questions.length * 5;
        return max > 0 ? Math.round((sum / max) * 100) : 0;
      }

      if (section.scoring === 'average-likert-100') {
        const avg = values.reduce((acc, value) => acc + value, 0) / values.length;
        return Math.round((avg / 5) * 100);
      }

      // sum-points-100
      const sum = values.reduce((acc, value) => acc + value, 0);
      const max = section.questions.length * 10;
      return max > 0 ? Math.round((sum / max) * 100) : 0;
    };

    const psicometricaSection = getSectionById('psicometrica');
    const cognitivaSection = getSectionById('cognitiva');
    const tecnicaSection = getSectionById('tecnica');
    const proyectivaSection = getSectionById('proyectiva');

    const psicometrica = psicometricaSection ? sectionScore(psicometricaSection) : 0;
    const cognitiva = cognitivaSection ? sectionScore(cognitivaSection) : 0;
    const tecnica = tecnicaSection ? sectionScore(tecnicaSection) : 0;
    const proyectiva = proyectivaSection ? sectionScore(proyectivaSection) : 0;

    return { psicometrica, cognitiva, tecnica, proyectiva };
  }

  getEvaluationItems(): { key: keyof SurveyResult; title: string; score: number; color: string; desc: string }[] {
    const items: { key: keyof SurveyResult; title: string; score: number; color: string; desc: string }[] = [
      {
        key: 'psicometrica',
        title: 'Pruebas Psicométricas',
        score: this.surveyResult.psicometrica,
        color: 'var(--primary-500)',
        desc: this.describeScore('psicometrica', this.surveyResult.psicometrica)
      },
      {
        key: 'cognitiva',
        title: 'Pruebas Cognitivas',
        score: this.surveyResult.cognitiva,
        color: 'var(--secondary)',
        desc: this.describeScore('cognitiva', this.surveyResult.cognitiva)
      },
      {
        key: 'tecnica',
        title: 'Pruebas Técnicas',
        score: this.surveyResult.tecnica,
        color: 'var(--accent)',
        desc: this.describeScore('tecnica', this.surveyResult.tecnica)
      },
      {
        key: 'proyectiva',
        title: 'Pruebas Proyectivas',
        score: this.surveyResult.proyectiva,
        color: 'var(--utc-brand)',
        desc: this.describeScore('proyectiva', this.surveyResult.proyectiva)
      }
    ];
    return items;
  }

  getOverallSurveyScore(): number {
    const values = Object.values(this.surveyResult);
    return Math.round(values.reduce((acc, value) => acc + value, 0) / values.length);
  }

  getSurveySummaryText(): string {
    const overall = this.getOverallSurveyScore();
    if (overall >= 85) {
      return 'Perfil con alta idoneidad: desempeño sobresaliente en competencias clave y buena estabilidad para entornos laborales exigentes.';
    }
    if (overall >= 70) {
      return 'Perfil con idoneidad media-alta: cumple el estándar general, con áreas puntuales de mejora para elevar competitividad.';
    }
    return 'Perfil en desarrollo: se recomienda plan de fortalecimiento en las categorías con menor puntaje antes de postulación crítica.';
  }

  isRecommendedCandidate(): boolean {
    return this.getOverallSurveyScore() >= 70;
  }

  private describeScore(category: keyof SurveyResult, score: number): string {
    const label =
      category === 'psicometrica'
        ? 'Rasgos conductuales'
        : category === 'cognitiva'
          ? 'Agilidad y razonamiento'
          : category === 'tecnica'
            ? 'Conocimiento específico'
            : 'Juicio situacional';
    if (score >= 85) return `${label}: nivel sobresaliente para vacantes de alta exigencia.`;
    if (score >= 70) return `${label}: desempeño sólido con oportunidad de afinar detalles.`;
    return `${label}: requiere refuerzo para alcanzar el umbral recomendado.`;
  }

  toggleTheme() {
    this.isLightTheme = !this.isLightTheme;
    localStorage.setItem('isLightTheme', JSON.stringify(this.isLightTheme));
    this.applyTheme();
  }

  private applyTheme() {
    const root = document.documentElement;
    if (this.isLightTheme) {
      root.classList.add('light-theme');
    } else {
      root.classList.remove('light-theme');
    }
  }

  toggleSidebar() {
    this.isSidebarOpen = !this.isSidebarOpen;
  }

  private renderCharts() {
    if (this.currentView === 'dashboard') {
    } else if (this.currentView === 'profile') {
      this.initCompetenciesChart('radarChartProfile');
    } else if (this.currentView === 'analitica' && this.analiticaPanelId === 'resumen') {
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
            'rgba(0, 61, 51, 0.88)',
            'rgba(20, 143, 92, 0.88)',
            'rgba(201, 162, 39, 0.88)',
            'rgba(10, 92, 78, 0.88)'
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
            ticks: { color: '#757575' }
          },
          x: {
            grid: { display: false },
            ticks: { color: '#757575' }
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
            backgroundColor: 'rgba(20, 143, 92, 0.22)',
            borderColor: 'rgba(20, 143, 92, 1)',
            pointBackgroundColor: 'rgba(20, 143, 92, 1)',
            borderWidth: 2
          },
          {
            label: 'Perfil Real (Egresado)',
            data: [75, 85, 95, 85, 85, 75],
            backgroundColor: 'rgba(0, 61, 51, 0.2)',
            borderColor: 'rgba(0, 61, 51, 1)',
            pointBackgroundColor: 'rgba(0, 61, 51, 1)',
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
            pointLabels: { color: '#6b7280', font: { size: 12 } },
            ticks: { display: false }
          }
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: { color: '#6b7280' }
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
          backgroundColor: 'rgba(0, 61, 51, 0.85)',
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
            grid: { color: 'rgba(0, 61, 51, 0.08)' },
            ticks: { color: '#757575' }
          },
          y: {
            grid: { display: false },
            ticks: { color: '#757575' }
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
            'rgba(20, 143, 92, 0.88)',
            'rgba(201, 162, 39, 0.88)',
            'rgba(0, 61, 51, 0.88)'
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
            labels: { color: '#757575' }
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

    const destLat = parseFloat(String(this.selectedEmpresa.Latitud || '21.5095'));
    const destLon = parseFloat(String(this.selectedEmpresa.Longitud || '-104.8956'));
    // Origen relativo al destino (demo): evita la misma ruta fija para todas las empresas
    const originLat = (destLat + 0.028).toFixed(4);
    const originLon = (destLon - 0.024).toFixed(4);
    const mapUrl = `https://maps.google.com/maps?saddr=${originLat},${originLon}&daddr=${destLat},${destLon}&output=embed`;
    this.mapaUrlSeguro = this.sanitizer.bypassSecurityTrustResourceUrl(mapUrl);
  }

  registrarEmpresa() {
    alert('Modulo de Registro de Empresa en desarrollo.');
  }

  /** true si la app corre en el dev server de Angular (puerto típico distinto de 80/443). */
  private useDevBackendProxy(): boolean {
    if (typeof window === 'undefined') return false;
    const h = window.location.hostname;
    if (h !== 'localhost' && h !== '127.0.0.1') return false;
    const p = window.location.port;
    if (p === '' || p === '80' || p === '443') return false;
    return true;
  }

  /**
   * Con `ng serve` usa el proxy (mismo origen, sin CORS hacia XAMPP).
   * Con la app servida por Apache (puerto 80), llama al PHP en el mismo host.
   */
  private buildInegiBuscarUrl(keyword: string, lat: string, lon: string, radio: string): string {
    const q = new URLSearchParams({
      keyword,
      lat,
      lon,
      radio
    }).toString();
    if (this.useDevBackendProxy()) {
      return `/backend-api/inegi-buscar.php?${q}`;
    }
    const proto = typeof window !== 'undefined' ? window.location.protocol : 'http:';
    const host = typeof window !== 'undefined' ? window.location.host : 'localhost';
    return `${proto}//${host}/HACKATHON-26/backend/inegi-buscar.php?${q}`;
  }

  async buscarEmpresasINEGI(keyword: string) {
    if (!keyword.trim()) {
      this.inegiError = 'Por favor, ingresa una palabra clave o carrera.';
      return;
    }

    this.isSearchingInegi = true;
    this.inegiResults = [];
    this.inegiError = '';
    this.inegiUsingMock = false;

    const lat = '21.50';
    const lon = '-104.89';
    const radio = '5000';
    const apiUrl = this.buildInegiBuscarUrl(keyword, lat, lon, radio);

    try {
      const response = await fetch(apiUrl);
      const payload = await response.json().catch(() => null) as {
        results?: any[];
        source?: string;
        error?: string;
        detalle?: string;
        http?: number;
        cuerpo?: string;
      } | null;

      if (!response.ok) {
        const msg =
          payload?.error ||
          payload?.detalle ||
          (payload?.http ? `HTTP ${payload.http}` : '') ||
          `Error HTTP ${response.status}`;
        this.inegiError = msg || 'Error al consultar el backend.';
        return;
      }

      if (!payload || !Array.isArray(payload.results)) {
        this.inegiError = 'Respuesta inválida del backend (falta results).';
        return;
      }

      this.inegiUsingMock = payload.source !== 'inegi';

      const data = payload.results;
      this.inegiResults = data.filter(empresa => empresa.Estrato !== '0 a 5 personas');
      if (this.inegiResults.length > 0) {
        this.verMapa(this.inegiResults[0]);
      } else {
        this.inegiError = 'No se encontraron empresas medianas/grandes en el radio especificado.';
      }
    } catch (err: unknown) {
      const extra = this.useDevBackendProxy()
        ? ' Con `ng serve` debe existir `src/proxy.conf.json` y reiniciar Angular tras cambiarlo.'
        : '';
      const detail = err instanceof Error ? ` (${err.message})` : '';
      this.inegiError =
        'No se pudo conectar al backend (CORS, Apache apagado o ruta incorrecta).' +
        extra +
        detail +
        ' URL de referencia: ' +
        this.backendBolsaUrl;
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

    const seed = this.hashKeyword(keyword);
    const latBase = 21.48 + (seed % 7) * 0.012;
    const lonBase = -104.92 + (seed % 5) * 0.018;
    return [
      { Nombre: `Empresa Comercializadora de ${keyword}`, Clase_actividad: 'Comercio al por mayor y menor', Estrato: '51 a 250 personas', Municipio: 'Tepic', Entidad: 'Nayarit', Latitud: latBase.toFixed(4), Longitud: lonBase.toFixed(4) },
      { Nombre: `Grupo Industrial ${keyword}`, Clase_actividad: 'Servicios de manufactura y distribucion', Estrato: '11 a 50 personas', Municipio: 'Xalisco', Entidad: 'Nayarit', Latitud: (latBase - 0.04).toFixed(4), Longitud: (lonBase + 0.03).toFixed(4) },
      { Nombre: `Logistica y Distribucion ${keyword}`, Clase_actividad: 'Transporte de carga', Estrato: '31 a 50 personas', Municipio: 'Tepic', Entidad: 'Nayarit', Latitud: (latBase + 0.02).toFixed(4), Longitud: (lonBase - 0.02).toFixed(4) },
      { Nombre: `Servicios Especializados ${keyword}`, Clase_actividad: 'Servicios profesionales', Estrato: '6 a 10 personas', Municipio: 'Compostela', Entidad: 'Nayarit', Latitud: (latBase - 0.08).toFixed(4), Longitud: (lonBase + 0.05).toFixed(4) }
    ];
  }

  private hashKeyword(s: string): number {
    let h = 0;
    for (let i = 0; i < s.length; i++) h = (Math.imul(31, h) + s.charCodeAt(i)) | 0;
    return Math.abs(h);
  }

  descargarPDF() {
    window.print();
  }

  /** URL a un script PHP en `backend/` (mismo patrón que inegi-buscar + proxy). */
  private buildBackendScriptUrl(scriptFile: string): string {
    const path = `/${scriptFile}`;
    if (this.useDevBackendProxy()) {
      return `/backend-api${path}`;
    }
    const proto = typeof window !== 'undefined' ? window.location.protocol : 'http:';
    const host = typeof window !== 'undefined' ? window.location.host : 'localhost';
    return `${proto}//${host}/HACKATHON-26/backend${path}`;
  }

  toggleAnaliticaReportesMenu(evt: MouseEvent): void {
    evt.preventDefault();
    this.reportesMenuOpen = !this.reportesMenuOpen;
    if (this.reportesMenuOpen && this.currentView !== 'analitica') {
      this.switchView('analitica');
    }
  }

  seleccionarReporteDesdeSidebar(
    panel: 'resumen' | 'egresados_carrera' | 'postulaciones_prueba' | 'vacantes_prueba'
  ): void {
    if (!this.canAccessView('analitica')) return;
    this.reportesMenuOpen = true;
    this.isSidebarOpen = false;
    if (this.currentView !== 'analitica') {
      this.switchView('analitica', { analiticaPanel: panel });
      if (panel === 'egresados_carrera') {
        window.setTimeout(() => void this.cargarReporteEgresadosPorCarrera(), 220);
      }
      return;
    }
    if (this.analiticaPanelId === panel) return;
    this.aplicarAnaliticaPanel(panel);
  }

  private aplicarAnaliticaPanel(
    panel: 'resumen' | 'egresados_carrera' | 'postulaciones_prueba' | 'vacantes_prueba'
  ): void {
    this.analiticaPanelId = panel;
    for (const key in this.chartInstances) {
      if (this.chartInstances[key]) {
        this.chartInstances[key].destroy();
      }
    }
    this.chartInstances = {};
    if (panel === 'egresados_carrera') {
      void this.cargarReporteEgresadosPorCarrera();
    }
    if (panel === 'resumen') {
      window.setTimeout(() => this.renderCharts(), 150);
    }
  }

  tituloAnaliticaPanelActual(): string {
    const m: Record<string, string> = {
      resumen: 'Resumen — gráficas y KPI',
      egresados_carrera: 'Egresados por carrera',
      postulaciones_prueba: 'Postulaciones por estatus (prueba)',
      vacantes_prueba: 'Vacantes por empresa (prueba)'
    };
    return m[this.analiticaPanelId] ?? '';
  }

  private mockEgresadosPorCarrera(): {
    id_carrera: string;
    carrera: string;
    total_egresados: number;
    con_examenes_completos: number;
  }[] {
    return [
      { id_carrera: '1', carrera: 'Ingeniería en Software', total_egresados: 48, con_examenes_completos: 36 },
      { id_carrera: '2', carrera: 'Administración', total_egresados: 32, con_examenes_completos: 22 },
      { id_carrera: '3', carrera: 'Contaduría', total_egresados: 24, con_examenes_completos: 19 },
      { id_carrera: '4', carrera: 'Arquitectura', total_egresados: 15, con_examenes_completos: 10 }
    ];
  }

  async cargarReporteEgresadosPorCarrera(): Promise<void> {
    this.reporteEgresadosCarreraLoading = true;
    this.reporteEgresadosCarreraUsaPrueba = false;
    this.reporteEgresadosCarreraFilas = [];
    const url = this.buildBackendScriptUrl('reporte-egresados-por-carrera.php');
    try {
      const res = await fetch(url);
      const body = (await res.json().catch(() => null)) as {
        filas?: unknown[];
        error?: string;
        detalle?: string;
      } | null;
      if (!res.ok) {
        this.reporteEgresadosCarreraFilas = this.mockEgresadosPorCarrera();
        this.reporteEgresadosCarreraUsaPrueba = true;
        return;
      }
      if (!body?.filas || !Array.isArray(body.filas)) {
        this.reporteEgresadosCarreraFilas = this.mockEgresadosPorCarrera();
        this.reporteEgresadosCarreraUsaPrueba = true;
        return;
      }
      const mapped = body.filas.map((r: any) => ({
        id_carrera: String(r.id_carrera ?? ''),
        carrera: String(r.carrera ?? ''),
        total_egresados: Number(r.total_egresados ?? 0),
        con_examenes_completos: Number(r.con_examenes_completos ?? 0)
      }));
      if (mapped.length === 0) {
        this.reporteEgresadosCarreraFilas = this.mockEgresadosPorCarrera();
        this.reporteEgresadosCarreraUsaPrueba = true;
      } else {
        this.reporteEgresadosCarreraFilas = mapped;
      }
    } catch {
      this.reporteEgresadosCarreraFilas = this.mockEgresadosPorCarrera();
      this.reporteEgresadosCarreraUsaPrueba = true;
    } finally {
      this.reporteEgresadosCarreraLoading = false;
    }
  }

  porcentajeExamenesCarrera(r: { total_egresados: number; con_examenes_completos: number }): string {
    if (!r.total_egresados) return '—';
    return `${((r.con_examenes_completos / r.total_egresados) * 100).toFixed(1)}%`;
  }

  /** Muestra estatus_postulacion legible (p. ej. en_proceso → En proceso). */
  formatEstatusPostulacion(raw: string): string {
    if (!raw) return '';
    return raw.replace(/_/g, ' ');
  }
}