<template>
  <div class="submission-pages-viewer">
    <div class="submission-pages-toolbar d-flex flex-wrap align-items-center">
      <span v-if="isMultiPage" class="mr-2 small font-weight-bold">Page</span>
      <span v-if="isMultiPage" class="mr-3 d-inline-flex flex-wrap" role="group" aria-label="Go to page">
        <button v-for="pageImage in pages"
                :key="`jump-${pageImage.page}`"
                type="button"
                class="page-chip"
                :class="{ active: currentPage === pageImage.page }"
                :aria-current="currentPage === pageImage.page ? 'page' : null"
                :aria-label="`Page ${pageImage.page}${hasMarkup(pageImage.page) ? ', marked up' : ''}`"
                :title="hasMarkup(pageImage.page) ? 'Marked up' : ''"
                @click="jumpToPage(pageImage.page, true)"
        >
          {{ pageImage.page }}<span v-if="hasMarkup(pageImage.page)" class="page-chip-dot"/>
        </button>
      </span>
      <b-button-group size="sm" class="mr-3" aria-label="Page size">
        <b-button :pressed="fit === 'page'" variant="outline-secondary" @click="setFit('page')">
          Fit Page
        </b-button>
        <b-button :pressed="fit === 'width'" variant="outline-secondary" @click="setFit('width')">
          Fit Width
        </b-button>
      </b-button-group>
      <b-button v-if="canAnnotate && currentPageImage"
                size="sm"
                variant="primary"
                class="ml-auto"
                :disabled="!annotationsLoaded"
                @click="openEditor(currentPageImage)"
      >
        <span v-if="!annotationsLoaded">
          <b-spinner v-if="!annotationsError" small class="mr-1"/>
          {{ annotationsError ? 'Markup unavailable' : 'Loading markup…' }}
        </span>
        <span v-else-if="isMultiPage">{{ hasMarkup(currentPage) ? 'Edit Markup on' : 'Mark Up' }} Page {{ currentPage }}</span>
        <span v-else>{{ hasMarkup(currentPage) ? 'Edit Markup' : 'Mark Up' }}</span>
      </b-button>
    </div>
    <div ref="scrollArea" class="submission-pages-scroll" @scroll="onScroll">
      <div v-for="pageImage in pages"
           :key="`page-${pageImage.page}-${pageImage.url}`"
           :ref="`page-${pageImage.page}`"
           class="submission-page"
      >
        <div v-if="isMultiPage" class="submission-page-label small text-muted">
          Page {{ pageImage.page }}<span v-if="hasMarkup(pageImage.page)"> · marked up</span>
        </div>
        <div :ref="`view-${pageImage.page}`"
             class="submission-page-view"
             :class="fit === 'page' ? 'zoom-in' : 'zoom-out'"
             :title="fit === 'page' ? 'Click to fit width' : 'Click to fit page'"
             @click="toggleFit(pageImage.page)"
        />
      </div>
    </div>

    <b-modal :id="modalId"
             :title="editingPage ? `Mark Up Page ${editingPage.page}` : 'Mark Up'"
             size="xl"
             dialog-class="submission-page-editor-dialog"
             no-close-on-backdrop
             no-close-on-esc
             hide-header-close
             @shown="mountEditor"
             @hidden="unmountEditor"
    >
      <div class="d-flex flex-wrap align-items-center mb-2">
        <b-button-group size="sm" class="mr-3 mb-1">
          <b-button :pressed="tool === 'select'" variant="outline-secondary" @click="useTool('select')">
            Select
          </b-button>
          <b-button :pressed="tool === 'ArrowMarker'" variant="outline-secondary" @click="useTool('ArrowMarker')">
            Arrow
          </b-button>
          <b-button :pressed="tool === 'FreehandMarker'" variant="outline-secondary" @click="useTool('FreehandMarker')">
            Draw
          </b-button>
          <b-button :pressed="tool === 'TextMarker'" variant="outline-secondary" @click="useTool('TextMarker')">
            Text
          </b-button>
        </b-button-group>
        <span class="mr-3 mb-1 d-inline-flex align-items-center">
          <button v-for="swatch in colors"
                  :key="swatch.value"
                  type="button"
                  class="color-swatch"
                  :class="{ active: color === swatch.value }"
                  :style="{ backgroundColor: swatch.value }"
                  :aria-label="`${swatch.name} ink`"
                  :title="swatch.name"
                  @click="useColor(swatch.value)"
          />
        </span>
        <b-button-group size="sm" class="mr-3 mb-1" aria-label="Size">
          <b-button v-for="option in sizes"
                    :key="option.value"
                    :pressed="size === option.value"
                    variant="outline-secondary"
                    :title="`${option.name} text and lines`"
                    @click="useSize(option.value)"
          >
            {{ option.label }}
          </b-button>
        </b-button-group>
        <b-button-group size="sm" class="mb-1">
          <b-button variant="outline-secondary" :disabled="!canUndo" @click="undo">
            Undo
          </b-button>
          <b-button variant="outline-secondary" :disabled="!canRedo" @click="redo">
            Redo
          </b-button>
          <b-button variant="outline-danger" @click="deleteSelected">
            Delete Selected
          </b-button>
        </b-button-group>
      </div>
      <div class="small text-muted mb-1">
        {{ toolHint }}
      </div>
      <div ref="editorContainer" class="submission-page-editor"/>
      <template #modal-footer>
        <b-button size="sm" :disabled="saving" @click="cancelEditing">
          Cancel
        </b-button>
        <b-button size="sm" variant="primary" :disabled="saving" @click="saveEditing">
          <b-spinner v-if="saving" small class="mr-1"/>
          Save Markup
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import axios from 'axios'
import { MarkerArea, MarkerView } from '@markerjs/markerjs3'

/**
 * Shows a student's submission pages as images, with any grader markup drawn on top, and lets graders mark up
 * a page with arrows, freehand drawing and text (marker.js 3).
 *
 * Markup is stored per assignment, question, student and page, at the page image's natural size, so it lines up
 * at any display width. It's saved as data and drawn over the untouched page image; nothing is burned in.
 */
export default {
  name: 'SubmissionPagesViewer',
  props: {
    pages: { type: Array, required: true }, // [{ page, url, width, height }]
    assignmentId: { type: Number, required: true },
    questionId: { type: Number, required: true },
    studentUserId: { type: Number, required: true },
    canAnnotate: { type: Boolean, default: false },
    // markup already fetched by the parent (the student view); when given, nothing is loaded from the server
    initialAnnotations: { type: Object, default: null }
  },
  data: () => ({
    annotations: {}, // page => AnnotationState
    // Mark Up stays disabled until the saved markup has loaded: opening the editor before then would start from a
    // blank page, and saving would overwrite the markup that's already there
    annotationsLoaded: false,
    resizeObserver: null,
    paneSize: { width: 0, height: 0 },
    pendingRender: false,
    annotationsError: false,
    currentPage: 1,
    fit: 'page', // 'page': whole page visible; 'width': page fills the pane's width
    scrollTicking: false,
    editingPage: null,
    markerArea: null,
    tool: 'select',
    color: '#d0021b',
    colors: [
      { name: 'Red', value: '#d0021b' },
      { name: 'Blue', value: '#1f5fd1' },
      { name: 'Green', value: '#1e8a3c' },
      { name: 'Black', value: '#111111' }
    ],
    size: 'medium',
    sizes: [
      { label: 'S', name: 'Small', value: 'small', factor: 0.6 },
      { label: 'M', name: 'Medium', value: 'medium', factor: 1 },
      { label: 'L', name: 'Large', value: 'large', factor: 1.6 }
    ],
    imageSize: { width: 1000, height: 1300 }, // natural size of the page being marked up
    canUndo: false,
    canRedo: false,
    changed: false,
    saving: false
  }),
  computed: {
    // a single photo or one-page PDF doesn't need page numbers
    isMultiPage () {
      return this.pages.length > 1
    },
    currentPageImage () {
      return this.pages.find(item => item.page === this.currentPage) || this.pages[0] || null
    },
    toolHint () {
      switch (this.tool) {
        case 'ArrowMarker':
          return 'Drag from where the arrow starts to where it points.'
        case 'FreehandMarker':
          return 'Hold the mouse button down and draw.'
        case 'TextMarker':
          return 'Click where the text should go, then type. Click outside the text when you\'re done.'
        default:
          return 'Click a mark to move or resize it, or to change its color or size. Double-click text to edit it.'
      }
    },
    modalId () {
      return `modal-mark-up-page-${this._uid}`
    },
    annotationsUrl () {
      return `/api/submission-page-annotations/assignment/${this.assignmentId}/question/${this.questionId}/student/${this.studentUserId}`
    }
  },
  watch: {
    pages () {
      this.currentPage = this.pages.length ? this.pages[0].page : 1
      this.$nextTick(() => this.renderAllViews())
    }
  },
  async mounted () {
    this.currentPage = this.pages.length ? this.pages[0].page : 1
    try {
      // remember the grader's choice between students; browser storage can be unavailable
      const savedFit = window.localStorage.getItem('submissionPagesFit')
      if (['page', 'width'].includes(savedFit)) {
        this.fit = savedFit
      }
    } catch (error) {
    }
    window.addEventListener('resize', this.renderAllViews)
    // Redraw when the pane's size changes, including going from hidden (0 wide) to shown: pages drawn while
    // hidden would otherwise be sized for nothing.
    if (window.ResizeObserver && this.$refs.scrollArea) {
      this.resizeObserver = new window.ResizeObserver(entries => {
        const width = Math.round(entries[0].contentRect.width)
        const height = Math.round(entries[0].contentRect.height)
        if (!width) {
          return
        }
        const changed = width !== this.paneSize.width || height !== this.paneSize.height
        const measuredBefore = this.paneSize.width > 0
        this.paneSize = { width, height }
        // pendingRender: pages were skipped while hidden. Otherwise only a real resize redraws (the first
        // measurement of a visible pane happens right after mounted() already drew the pages).
        if (this.pendingRender || (changed && measuredBefore)) {
          this.pendingRender = false
          this.renderAllViews()
        }
      })
      this.resizeObserver.observe(this.$refs.scrollArea)
    }
    if (this.initialAnnotations) {
      this.annotations = { ...this.initialAnnotations }
      this.annotationsLoaded = true
    } else {
      await this.loadAnnotations()
    }
    this.renderAllViews()
  },
  beforeDestroy () {
    window.removeEventListener('resize', this.renderAllViews)
    if (this.resizeObserver) {
      this.resizeObserver.disconnect()
    }
  },
  methods: {
    hasMarkup (page) {
      const state = this.annotations[page]
      return Boolean(state && state.markers && state.markers.length)
    },
    async loadAnnotations () {
      try {
        const { data } = await axios.get(this.annotationsUrl)
        if (data.type === 'error') {
          this.annotationsError = true
          this.$noty.error(data.message)
          return
        }
        this.annotations = data.annotations || {}
        this.annotationsLoaded = true
      } catch (error) {
        this.annotationsError = true
        this.$noty.error(error.message)
      }
    },
    renderAllViews () {
      return Promise.all(this.pages.map(pageImage => this.renderView(pageImage)))
    },
    pageElement (page) {
      const refs = this.$refs[`page-${page}`]
      return Array.isArray(refs) ? refs[0] : refs
    },
    // The page whose top has scrolled past 40% of the pane is the one being looked at.
    onScroll () {
      if (this.scrollTicking) {
        return
      }
      this.scrollTicking = true
      window.requestAnimationFrame(() => {
        this.scrollTicking = false
        const scrollArea = this.$refs.scrollArea
        if (!scrollArea) {
          return
        }
        const line = scrollArea.scrollTop + scrollArea.clientHeight * 0.4
        let current = this.pages.length ? this.pages[0].page : 1
        for (const pageImage of this.pages) {
          const element = this.pageElement(pageImage.page)
          if (element && element.offsetTop <= line) {
            current = pageImage.page
          }
        }
        this.currentPage = current
      })
    },
    jumpToPage (page, smooth = false) {
      const scrollArea = this.$refs.scrollArea
      const element = this.pageElement(page)
      if (!scrollArea || !element) {
        return
      }
      this.currentPage = page
      scrollArea.scrollTo({ top: element.offsetTop, behavior: smooth ? 'smooth' : 'auto' })
    },
    async setFit (fit) {
      if (this.fit === fit) {
        return
      }
      const page = this.currentPage
      this.fit = fit
      try {
        window.localStorage.setItem('submissionPagesFit', fit)
      } catch (error) {
      }
      await this.renderAllViews()
      this.jumpToPage(page)
    },
    toggleFit (page) {
      this.currentPage = page
      this.setFit(this.fit === 'page' ? 'width' : 'page')
    },
    // How big to draw a page: the pane's width, or small enough that the whole page fits in the pane.
    displaySize (container, size) {
      const scrollArea = this.$refs.scrollArea
      const availableWidth = Math.max(100, container.clientWidth || size.width)
      let width = availableWidth
      if (this.fit === 'page' && scrollArea) {
        const availableHeight = Math.max(200, scrollArea.clientHeight - (this.isMultiPage ? 40 : 18)) // room for the page label
        width = Math.min(availableWidth, availableHeight * size.width / size.height)
      }
      width = Math.round(width)
      return { width, height: Math.round(width * size.height / size.width) }
    },
    // Read-only display: a plain image, or a marker.js view when the page has markup.
    async renderView (pageImage) {
      const refs = this.$refs[`view-${pageImage.page}`]
      const container = Array.isArray(refs) ? refs[0] : refs
      if (!container) {
        return
      }
      if (!container.clientWidth && this.resizeObserver) {
        this.pendingRender = true // hidden: draw when the observer sees a real size
        return
      }
      while (container.firstChild) {
        container.removeChild(container.firstChild)
      }
      // a resize can start another render while this one waits for the image; only the latest one draws
      const renderToken = String(Date.now() + Math.random())
      container.dataset.renderToken = renderToken
      const img = document.createElement('img')
      img.src = pageImage.url
      img.alt = `Page ${pageImage.page} of the student's submission`
      img.addEventListener('error', () => this.$emit('imageError'), { once: true })

      const size = await this.naturalSize(img, pageImage)
      if (container.dataset.renderToken !== renderToken) {
        return
      }
      const { width, height } = this.displaySize(container, size)
      if (!this.hasMarkup(pageImage.page)) {
        img.className = 'submission-page-image'
        img.style.width = `${width}px`
        img.style.height = `${height}px`
        container.appendChild(img)
        return
      }
      const view = new MarkerView()
      view.targetImage = img
      view.targetWidth = width
      view.targetHeight = height
      // the element doesn't size itself to the target
      view.style.width = `${width}px`
      view.style.height = `${height}px`
      if (!container.dataset.wheelGuard) {
        // marker.js's view swallows the mouse wheel; let it scroll the page list instead
        container.addEventListener('wheel', event => event.stopPropagation(), { capture: true })
        container.dataset.wheelGuard = '1'
      }
      container.appendChild(view)
      // markers are rescaled from the page's natural size to this display size
      view.show(this.annotations[pageImage.page])
    },
    openEditor (pageImage) {
      if (!this.annotationsLoaded) {
        return
      }
      this.editingPage = pageImage
      this.tool = 'select'
      this.changed = false
      this.$bvModal.show(this.modalId)
    },
    // The size the browser actually shows the image at. For phone photos this already accounts for EXIF
    // rotation, which the server's recorded width/height don't, so markup always lines up with what's seen.
    naturalSize (img, pageImage) {
      const fallback = { width: pageImage.width || 1000, height: pageImage.height || 1300 }
      const read = () => (img.naturalWidth && img.naturalHeight
        ? { width: img.naturalWidth, height: img.naturalHeight }
        : fallback)
      if (img.complete && img.naturalWidth) {
        return Promise.resolve(read())
      }
      return new Promise(resolve => {
        img.addEventListener('load', () => resolve(read()), { once: true })
        img.addEventListener('error', () => resolve(fallback), { once: true })
      })
    },
    async mountEditor () {
      const container = this.$refs.editorContainer
      if (!container || !this.editingPage) {
        return
      }
      const img = document.createElement('img')
      img.src = this.editingPage.url
      const size = await this.naturalSize(img, this.editingPage)
      if (!this.editingPage) {
        return // closed while the image loaded
      }
      const markerArea = new MarkerArea()
      markerArea.targetImage = img
      // edit at the image's natural size so saved coordinates don't depend on the screen
      markerArea.targetWidth = size.width
      markerArea.targetHeight = size.height
      this.imageSize = size
      markerArea.autoZoomOut = true
      markerArea.addEventListener('areastatechange', () => {
        this.changed = true
        this.updateUndoRedo()
      })
      markerArea.addEventListener('markercreate', () => {
        this.tool = 'select'
        // a new text box opens with "Text" in a hidden text field; select it so the first keystroke replaces it
        setTimeout(() => {
          const field = markerArea.shadowRoot && markerArea.shadowRoot.activeElement
          if (field && field.tagName === 'TEXTAREA') {
            field.select()
          }
        }, 0)
      })
      container.appendChild(markerArea)
      const saved = this.annotations[this.editingPage.page]
      if (saved) {
        markerArea.restoreState(saved, false)
      }
      this.markerArea = markerArea
      this.updateUndoRedo()
    },
    unmountEditor () {
      const container = this.$refs.editorContainer
      if (container) {
        while (container.firstChild) {
          container.removeChild(container.firstChild)
        }
      }
      this.markerArea = null
      this.editingPage = null
    },
    updateUndoRedo () {
      this.canUndo = Boolean(this.markerArea && this.markerArea.isUndoPossible)
      this.canRedo = Boolean(this.markerArea && this.markerArea.isRedoPossible)
    },
    useTool (tool) {
      if (!this.markerArea) {
        return
      }
      this.tool = tool
      if (tool === 'select') {
        this.markerArea.switchToSelectMode()
        return
      }
      const editor = this.markerArea.createMarker(tool)
      if (editor) {
        this.applyColor(editor, this.color)
        this.applySize(editor, this.size)
      }
    },
    // Sizes are in the image's own pixels, so they scale with the image: about 2% of its longer side for text.
    // A phone photo can be 3000+ pixels wide, where marker.js's defaults (16px text, 3px lines) are unreadable.
    applySize (editor, size) {
      const option = this.sizes.find(item => item.value === size) || this.sizes[1]
      const longerSide = Math.max(this.imageSize.width, this.imageSize.height)
      if ('fontSize' in editor) {
        editor.fontSize = { value: Math.max(12, Math.round(longerSide * 0.02 * option.factor)), units: 'px', step: 1 }
      } else if ('strokeWidth' in editor) {
        editor.strokeWidth = Math.max(2, Math.round(longerSide * 0.0025 * option.factor))
      }
    },
    useSize (size) {
      this.size = size
      if (this.markerArea) {
        this.markerArea.selectedMarkerEditors.forEach(editor => this.applySize(editor, size))
      }
    },
    // Text editors have a text color; arrows and freehand use the stroke color.
    applyColor (editor, color) {
      if ('color' in editor) {
        editor.color = color
      } else if ('strokeColor' in editor) {
        editor.strokeColor = color
      }
    },
    useColor (color) {
      this.color = color
      if (this.markerArea) {
        this.markerArea.selectedMarkerEditors.forEach(editor => this.applyColor(editor, color))
      }
    },
    undo () {
      this.markerArea && this.markerArea.undo()
      this.updateUndoRedo()
    },
    redo () {
      this.markerArea && this.markerArea.redo()
      this.updateUndoRedo()
    },
    deleteSelected () {
      this.markerArea && this.markerArea.deleteSelectedMarkers()
      this.updateUndoRedo()
    },
    async cancelEditing () {
      if (this.changed) {
        const discard = await this.$bvModal.msgBoxConfirm('Discard your unsaved markup on this page?', {
          title: 'Unsaved Markup',
          size: 'sm',
          buttonSize: 'sm',
          okTitle: 'Discard',
          okVariant: 'danger',
          cancelTitle: 'Keep Editing',
          centered: true
        })
        if (!discard) {
          return
        }
      }
      this.$bvModal.hide(this.modalId)
    },
    async saveEditing () {
      if (!this.markerArea || !this.editingPage) {
        return
      }
      this.markerArea.switchToSelectMode() // commits any text still being typed
      const page = this.editingPage.page
      const state = this.markerArea.getState()
      this.saving = true
      try {
        const { data } = await axios.put(`${this.annotationsUrl}/page/${page}`, { state })
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return
        }
        this.$set(this.annotations, page, state.markers.length ? state : null)
        this.$noty.success(data.message)
        this.$bvModal.hide(this.modalId)
        this.$nextTick(() => {
          const pageImage = this.pages.find(item => item.page === page)
          if (pageImage) {
            this.renderView(pageImage)
          }
        })
      } catch (error) {
        this.$noty.error(error.message)
      } finally {
        this.saving = false
      }
    }
  }
}
</script>

<style>
.submission-pages-toolbar {
  gap: 4px 0;
  padding: 6px 8px;
  margin-bottom: 6px;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  background: #f8f9fa;
}

.page-chip {
  position: relative;
  min-width: 30px;
  height: 30px;
  margin: 2px 4px 2px 0;
  padding: 0 6px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  background: #fff;
  font-size: 0.875rem;
  cursor: pointer;
}

.page-chip.active {
  border-color: #1f5fd1;
  background: #1f5fd1;
  color: #fff;
}

.page-chip-dot {
  position: absolute;
  top: 3px;
  right: 3px;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #d0021b;
}

.page-chip.active .page-chip-dot {
  background: #fff;
}

/* the pages scroll inside the viewer, so the toolbar stays put */
.submission-pages-scroll {
  position: relative;
  height: calc(100vh - 240px);
  min-height: 360px;
  overflow-y: auto;
  padding-right: 4px;
}

.submission-page {
  margin-bottom: 16px;
}

.submission-page-label {
  height: 22px;
  text-align: center;
}

.submission-page-view.zoom-in {
  cursor: zoom-in;
}

.submission-page-view.zoom-out {
  cursor: zoom-out;
}

.submission-page-view .submission-page-image,
.submission-page-view mjs-marker-view {
  display: block;
  margin: 0 auto;
  border: 1px solid #dee2e6;
}

.submission-page-editor-dialog {
  max-width: 95vw;
}

.submission-page-editor {
  height: 75vh;
  border: 1px solid #dee2e6;
  background: #f8f9fa;
}

.submission-page-editor mjs-marker-area {
  display: block;
  width: 100%;
  height: 100%;
}

.color-swatch {
  width: 24px;
  height: 24px;
  margin-right: 6px;
  border-radius: 50%;
  border: 2px solid #fff;
  box-shadow: 0 0 0 1px #adb5bd;
  cursor: pointer;
}

.color-swatch.active {
  box-shadow: 0 0 0 2px #212529;
}
</style>
