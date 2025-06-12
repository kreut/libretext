import axios from 'axios'
import { initCentrifuge } from './Centrifuge'

export async function initClickerAssignmentsForEnrolledAndOpenCourses () {
  try {
    const { data } = await axios.get('/api/assignments/clicker/enrolled-open-courses')
    if (data.type !== 'success') {
      this.$noty.error(data.message)
      return
    }
    console.error(data)
    const clickerAssignments = data.clicker_assignments
    if (clickerAssignments.length) {
      console.error(clickerAssignments)
      this.centrifuge = await initCentrifuge()
      for (let i = 0; i < clickerAssignments.length; i++) {
        let assignment = clickerAssignments[i]
        let sub = this.centrifuge.newSubscription(`set-current-page-${assignment.id}`)
        console.error('New subscription: ' + assignment.id)
        sub.on('publication', async (ctx) => {
          console.error(ctx)
          const data = ctx.data
          this.clickerAssignmentId = +assignment.id
          this.clickerQuestionId = +data.question_id
          this.canViewClickerSubmissions = false
          this.clickerViewIsSubmissions = true
          this.redirectToClickerModalKey++
        }).subscribe()
      }
    }
  } catch (error) {
    this.$noty.error(error.message)
  }
}

export function resetClickerAssignmentIdClickerQuestionId () {
  this.clickerAssignmentId = 0
  this.clickerQuestionId = 0
}
