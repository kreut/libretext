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
      <th scope="col">
        Status
      </th>
    </tr>
    </thead>
    <tbody>
    <tr v-for="(assignTo,index) in assignTosToView" :key="`assignTos-${index}`">
      <td>{{ assignTo.groups.join(', ') }}</td>
      <td>
        {{ $moment(assignTo.available_from_date, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}<br>
        {{ $moment(assignTo.available_from_time, 'HH:mm:ss A').format('h:mm A') }}
      </td>
      <td>
        {{ $moment(assignTo.due_date, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}<br>
        {{ $moment(assignTo.due_time, 'HH:mm:ss A').format('h:mm A') }}
      </td>
      <td>
        {{ assignTo.status }}
      </td>
    </tr>
    </tbody>
  </table>
</template>

<script>
export default {
  name: 'AssignToModal',
  props: {
    'assignTosToView': {
      type: Array,
      default: function () {
        return { message: 'No assign Tos To View' }
      }
    }
  }
}
</script>

<style scoped>

</style>
