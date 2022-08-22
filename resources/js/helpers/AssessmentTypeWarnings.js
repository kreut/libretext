export function updateNonLearningTreeInLearningTreeMessage () {
  this.nonLearningTreeQuestionsInLearningTree = ''
  if (this.assessmentType === 'learning tree') {
    for (let i = 0; i < this.items.length; i++) {
      if (!this.items[i].learning_tree) {
        this.nonLearningTreeQuestionsInLearningTree += this.items[i].order + ', '
      }
    }
    if (this.nonLearningTreeQuestionsInLearningTree) {
      this.nonLearningTreeQuestionsInLearningTree = this.nonLearningTreeQuestionsInLearningTree.replace(new RegExp(', $'), '')
    }
  }
}

export function updateH5pNonAdaptQuestionsMessage () {
  this.h5pNonAdaptQuestions = ''
  let numIssues = 0
  for (let i = 0; i < this.items.length; i++) {
    if (this.items[i].h5p_non_adapt) {
      numIssues++
      this.h5pNonAdaptQuestions += `${this.items[i].order} (${this.items[i].h5p_non_adapt}), `
    }
  }
  if (this.h5pNonAdaptQuestions && numIssues > 1) {
    this.h5pNonAdaptQuestions = this.h5pNonAdaptQuestions.replace(new RegExp(', $'), '')
  }
}

export function updateLearningTreeInNonLearningTreeMessage () {
  this.learningTreeQuestionsInNonLearningTree = ''
  let numIssues = 0
  if (this.assessmentType !== 'learning tree') {
    for (let i = 0; i < this.items.length; i++) {
      if (this.items[i].learning_tree) {
        numIssues++
        this.learningTreeQuestionsInNonLearningTree += this.items[i].order + ', '
      }
    }
    if (this.learningTreeQuestionsInNonLearningTree && numIssues > 1) {
      this.learningTreeQuestionsInNonLearningTree = this.learningTreeQuestionsInNonLearningTree.replace(new RegExp(', $'), '')
    }
  }
}

export function h5pText () {
  return 'This assignment has non-H5P assessments. Clicker assignments can only be used with H5P true-false and H5P multiple choice assessments. Please remove any non-H5P assessments.'
}

export function updateOpenEndedInRealTimeMessage () {
  this.openEndedQuestionsInRealTime = ''
  let numIssues = 0
  if (this.assessmentType !== 'delayed') {
    for (let i = 0; i < this.items.length; i++) {
      if (!this.items[i].auto_graded_only) {
        numIssues++
        this.openEndedQuestionsInRealTime += this.items[i].order + ', '
      }
    }
    if (this.openEndedQuestionsInRealTime && numIssues > 1) {
      this.openEndedQuestionsInRealTime = this.openEndedQuestionsInRealTime.replace(new RegExp(', $'), '')
    }
  }
}

