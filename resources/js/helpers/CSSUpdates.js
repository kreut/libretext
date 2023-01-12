export const h5pOnLoadCssUpdates = {
  elements: [
    {
      selector: '.h5p-content',
      style: 'border:none;'
    }
  ]
}

export const webworkOnLoadCssUpdates = {
  elements: [
    {
      selector: 'div#problem_body',
      style: 'background: none;border: none;box-shadow: none'
    },
    {
      selector: 'input[name="submitAnswers"]',
      class: 'btn btn-sm btn-primary'
    }, {
      selector: 'input[name="previewAnswers"]',
      class: 'btn btn-sm btn-primary'
    }
  ],
  templates: [
    '.btn-primary:not(:hover) {background-color: #0058E6 !important;}',
    '.btn-primary:hover, .btn-primary:focus {color: #0058E6 !important;background-color: white !important;}'
  ]
}

export const webworkStudentCssUpdates = {
  elements: [
    {
      selector: 'input[name="submitAnswers"]',
      style: 'pointer-events: none;opacity: 0.5 !important'
    }, {
      selector: 'input[name="previewAnswers"]',
      style: 'pointer-events: none;opacity: 0.5 !important'
    }
  ],
  templates: [
    '.btn-primary:not(:hover) {background-color: #0058E6 !important;}',
    '.btn-primary:hover, .btn-primary:focus {color: #0058E6 !important;background-color: white !important;}'
  ]
}
