<template>
  <b-form-group> <span class="pr-1">Time Spent  <QuestionCircleTooltip :id="'time-spent-tooltip'"/>
          <b-tooltip target="time-spent-tooltip"
                     delay="250"
                     triggers="hover focus"
          >
            For the "time on task" view, the timer starts when a student visits a question and stops when the student makes a submission.
            If they revisit the question, time will be added to the
            current value.  For the "time in review" view, the timer starts when a student visits a question for a closed assignment and stops
            when the student leaves the question.
          </b-tooltip></span>
    <b-form-select id="time_spent"
                   v-model="timeSpent"
                   style="width:150px"
                   :options="timeSpentOptions"
                   @change="$emit('updateView',$event)"
    />
  </b-form-group>
</template>

<script>
export default {
  name: 'TimeSpent',
  data: () => ({
    timeSpent: 'hidden',
    timeSpentOptions: [
      { value: 'hidden', text: 'Hidden' },
      { value: 'on_task', text: 'On Task' },
      { value: 'in_review', text: 'In Review' }
    ],
  })
}
</script>

