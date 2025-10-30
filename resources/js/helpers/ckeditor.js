import { fixCKEditor } from '~/helpers/accessibility/fixCKEditor'

export function handleFixCKEditorWithPasteWarning (editor) {
  console.error('CKEditor ready:', editor.name)
  this.pastedContent = false
  // Utility: strip HTML tags
  const stripHtml = (html) => {
    const div = document.createElement('div')
    div.innerHTML = html
    return div.textContent || div.innerText || ''
  }

  editor.on('paste', (pasteEvt) => {
    console.log('CKEditor paste event fired!')
    const pastedHtml = pasteEvt.data.dataValue
    pasteEvt.data.dataValue = stripHtml(pastedHtml)
    this.$bvModal.show('modal-confirm-paste-into-ckeditor')
  })

  // 2️⃣ Native DOM paste fallback (after insertion)
  editor.on('contentDom', () => {
    editor.document.on('paste', () => {
      setTimeout(() => {
        const htmlAfterPaste = editor.getData()
        const plainText = stripHtml(htmlAfterPaste)

        // Replace the entire content with plain text
        editor.setData(plainText)

        console.log('Sanitized content after native paste:', plainText)
        this.$bvModal.show('modal-confirm-paste-into-ckeditor')
      }, 0)
    })
  })
  fixCKEditor(this)
}
