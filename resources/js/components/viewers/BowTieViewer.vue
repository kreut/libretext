<template>
  <b-container>
    <b-row class="text-center pb-3" align-v="center">
      <b-col>
        <div class="pb-3">
          <b-card style="background-color:#EDF5F4;">
            Action To Take
          </b-card>
        </div>
        <b-card style="background-color:#EDF5F4;">Action To Take</b-card>
      </b-col>
      <b-col>
        <b-card>Condition Most Likely Experiencing</b-card>
      </b-col>
      <b-col>
        <div class="pb-3">
          <b-card>Parameter To Monitor</b-card>
        </div>
        <b-card>Parameter To Monitor</b-card>
      </b-col>
    </b-row>
    <b-row>
      <b-col>
        <b-card
          no-body
        >
          <template #header>
            <span >Actions To Take</span>
          </template>
          <b-list-group flush>
            <b-list-group-item v-for="(actionToTake, actionToTakeIndex) in qtiJson.actionsToTake"
                               :key="`action-to-take-${actionToTake.identifier}`"
            >
              {{ actionToTake.value }}
            </b-list-group-item>

          </b-list-group>
        </b-card>
      </b-col>
      <b-col>sdffsd</b-col>
      <b-col>sdfdsf</b-col>
    </b-row>
  </b-container>
</template>

<script>
export default {
  name: 'BowTie',
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    }
  }
}
</script>

