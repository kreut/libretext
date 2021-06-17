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

export function updateLearningTreeInNonLearningTreeMessage () {
  this.learningTreeQuestionsInNonLearningTree = ''
  if (this.assessmentType !== 'learning tree') {
    for (let i = 0; i < this.items.length; i++) {
      if (this.items[i].learning_tree) {
        this.learningTreeQuestionsInNonLearningTree += this.items[i].order + ', '
      }
    }
    if (this.learningTreeQuestionsInNonLearningTree) {
      this.learningTreeQuestionsInNonLearningTree = this.learningTreeQuestionsInNonLearningTree.replace(new RegExp(', $'), '')
    }
  }
}

export function h5pText () {
  return 'This assignment has non-H5P assessments. Clicker assignments can only be used with H5P true-false and H5P multiple choice assessments. Please remove any non-H5P assessments.'
}

export function updateOpenEndedInRealTimeMessage () {
  this.openEndedQuestionsInRealTime = ''
  if (this.assessmentType !== 'delayed') {
    for (let i = 0; i < this.items.length; i++) {
      if (!this.items[i].auto_graded_only) {
        this.openEndedQuestionsInRealTime += this.items[i].order + ', '
      }
    }
    if (this.openEndedQuestionsInRealTime) {
      this.openEndedQuestionsInRealTime = this.openEndedQuestionsInRealTime.replace(new RegExp(', $'), '')
    }
  }
}

