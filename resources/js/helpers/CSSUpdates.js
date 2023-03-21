export const h5pOnLoadCssUpdates = {
  elements: [
    {
      selector: '.h5p-content',
      style: 'border:none;'
    }
  ]
}

const iframeTextType = window.self !== window.top ? ';font-size:17.6px;font-weight:400;color:#000000;font-family:Tahoma, Ariel, serif' : ''
export const webworkOnLoadCssUpdates = {
  elements: [
    {
      selector: 'div#problem_body',
      style: 'padding-top:0px;background: none;border: none;box-shadow: none' + iframeTextType
    },
    {
      selector: '.attemptResultsHeader',
      style: 'display:none'
    },
    {
      selector: 'table.attemptResults',
      style: 'display:none'
    },
    {
      selector: 'div.attemptResultsSummary',
      style: 'display:none'
    },
    {
      selector: 'input[name="submitAnswers"]',
      class: 'btn btn-sm btn-primary'
    },
    {
      selector: 'input[name="previewAnswers"]',
      style: 'display:none'
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
    }
  ],
  templates: [
    '.btn-primary:not(:hover) {background-color: #0058E6 !important;}',
    '.btn-primary:hover, .btn-primary:focus {color: #0058E6 !important;background-color: white !important;}'
  ]
}
