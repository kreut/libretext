<template>
  <div>
    <textarea ref="textarea" />
  </div>
</template>

<script>
import CodeMirror from 'codemirror'
import 'codemirror/lib/codemirror.css'
import 'codemirror/mode/perl/perl.js'

export default {
  name: 'CodeMirrorEditor',

  props: {
    value: {
      type: String,
      default: ''
    },
    isInvalid: {
      type: Boolean,
      default: false
    }
  },

  data: () => ({
    editor: null
  }),

  mounted () {
    this.editor = CodeMirror.fromTextArea(this.$refs.textarea, {
      mode: 'perl',
      lineNumbers: true,
      indentWithTabs: true,
      indentUnit: 4,
      lineWrapping: false,
      theme: 'default',
      gutters: ['CodeMirror-linenumbers']
    })

    this.editor.setValue(this.value || '')

    this.editor.on('change', (cm) => {
      this.$emit('input', cm.getValue())
    })

    this.editor.setSize(null, '300px')

    this._observer = new ResizeObserver(() => {
      if (this.$el.offsetWidth > 0) {
        this.editor.refresh()
      }
    })
    this._observer.observe(this.$el)

    // Force a refresh after the modal has fully rendered
    this.$nextTick(() => {
      this.editor.refresh()
    })

    if (this.isInvalid) {
      this.editor.getWrapperElement().classList.add('is-invalid-cm')
    }
  },

  beforeDestroy () {
    if (this._observer) this._observer.disconnect()
    if (this.editor) {
      this.editor.toTextArea()
    }
  },

  watch: {
    value (newVal) {
      if (!this.editor) return
      if (newVal !== this.editor.getValue()) {
        this.editor.setValue(newVal || '')
      }
    },
    isInvalid (val) {
      if (!this.editor) return
      this.editor.getWrapperElement().classList.toggle('is-invalid-cm', val)
    }
  }
}
</script>

<style>
.CodeMirror {
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 13px;
  font-family: monospace;
  height: 300px;
}

.CodeMirror-focused {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.is-invalid-cm {
  border: 1px solid #dc3545 !important;
}

.CodeMirror-gutters {
  left: 0 !important;
  position: absolute !important;
}

.CodeMirror-linenumber {
  padding: 0 8px 0 5px !important;
  min-width: 20px !important;
  text-align: right !important;
  color: #999 !important;
  white-space: nowrap !important;
}

.CodeMirror pre.CodeMirror-line,
.CodeMirror pre.CodeMirror-line-like {
  padding-left: 4px !important;
}
</style>
