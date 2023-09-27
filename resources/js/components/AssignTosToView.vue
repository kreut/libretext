<template>
  <table class="table table-striped">
    <thead>
      <tr>
        <th scope="col">
          Group
        </th>
        <th scope="col">
          Available From
        </th>
        <th scope="col">
          Due
        </th>
        <th v-if="assignTosToView[0].final_submission_deadline" scope="col">
          Final Submission Deadline
        </th>
        <th scope="col">
          Status
        </th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="(assignTo,index) in assignTosToView" :key="`assignTos-${index}`">
        <td>{{ assignTo.groups.join(', ') }}</td>
        <td>
          {{ $moment(assignTo.available_from_date, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}
          {{ $moment(assignTo.available_from_time, 'HH:mm:ss A').format('h:mm A') }}
        </td>
        <td>
          {{ $moment(assignTo.due_date, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}
          {{ $moment(assignTo.due_time, 'HH:mm:ss A').format('h:mm A') }}
        </td>
        <td v-if="assignTosToView[0].final_submission_deadline">
          {{ $moment(assignTo.final_submission_deadline_date, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}
          {{ $moment(assignTo.final_submission_deadline_time, 'HH:mm:ss A').format('h:mm A') }}
        </td>
        <td>
          <span :class="getStatusTextClass(assignTo.status)">{{ assignTo.status }}</span>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script>
import { getStatusTextClass } from '~/helpers/AssignTosStatus'
export default {
  name: 'AssignToModal',
  props: {
    'assignTosToView': {
      type: Array,
      default: function () {
        return { message: 'No assign Tos To View' }
      }
    }
  },
  methods: {
    getStatusTextClass
  }
}
</script>

<style scoped>

</style>
