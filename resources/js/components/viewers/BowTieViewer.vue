<template>
  <b-container>
    <b-row class="text-center pb-3" align-v="center">
      <b-col>
        <div v-for="index in [0,1]" :key="`action-to-take-${index}`" class="pb-3">
          <b-card class="action-to-take">
            <div v-if="!selectedActionsToTake[index]">
              Action To Take
            </div>
            <div v-else>
              {{
                qtiJson.actionsToTake.find(actionToTake => actionToTake.identifier === selectedActionsToTake[index]).value
              }}
            </div>
          </b-card>
        </div>
      </b-col>
      <b-col>
        <b-card class="potential-conditions">
          <div v-if="!selectedPotentialCondition.length">
            Condition Most Likely Experiencing
          </div>
          <div v-else>
            {{
              qtiJson.potentialConditions.find(potentialCondition => potentialCondition.identifier === selectedPotentialCondition[0]).value
            }}
          </div>
        </b-card>
      </b-col>
      <b-col>
        <div v-for="index in [0,1]" :key="`parameters-to-monitor-${index}`" class="pb-3">
          <b-card class="parameters-to-monitor">
            <div v-if="!selectedParametersToMonitor[index]">
              Parameters To Monitor
            </div>
            <div v-else>
              {{
                qtiJson.parametersToMonitor.find(parameterToMonitor => parameterToMonitor.identifier === selectedParametersToMonitor[index]).value
              }}
            </div>
          </b-card>
        </div>
      </b-col>
    </b-row>
    <b-row>
      <b-col>
        <b-card
          no-body
        >
          <b-list-group flush>
            <b-list-group-item class="bow-tie list-group-item-header font-weight-bold text-center">
              Actions To Take
            </b-list-group-item>
            <b-form>
              <b-form-checkbox-group
                id="action-to-take-checkbox"
                v-model="selectedActionsToTake"
              >
                <b-list-group-item v-for="(actionToTake, actionToTakeIndex) in qtiJson.actionsToTake"
                                   :key="`action-to-take-${actionToTakeIndex}`"
                                   style="font-size:12px"
                                   class="m-1 action-to-take"
                >
                  <b-form-checkbox :value="actionToTake.identifier"> {{ actionToTake.value }}</b-form-checkbox>
                </b-list-group-item>
              </b-form-checkbox-group>
            </b-form>
          </b-list-group>
        </b-card>
      </b-col>
      <b-col>
        <b-card
          no-body
        >
          <b-list-group flush>
            <b-list-group-item class="bow-tie list-group-item-header font-weight-bold text-center">
              Potential Conditions
            </b-list-group-item>
            <b-form>
              <b-form-checkbox-group
                id="potential-condition-checkbox"
                v-model="selectedPotentialCondition"
              >
                <b-list-group-item v-for="(potentialCondition, potentialConditionIndex) in qtiJson.potentialConditions"
                                   :key="`potential-condition-${ potentialConditionIndex}`"
                                   style="font-size:12px"
                                   class="m-1 potential-conditions"
                >
                  <b-form-checkbox :value="potentialCondition.identifier"> {{
                      potentialCondition.value
                    }}
                  </b-form-checkbox>
                </b-list-group-item>
              </b-form-checkbox-group>
            </b-form>

          </b-list-group>
        </b-card>
      </b-col>
      <b-col>
        <b-card
          no-body
        >
          <b-list-group flush>
            <b-list-group-item class="bow-tie list-group-item-header font-weight-bold text-center">
              Parameters To Monitor
            </b-list-group-item>
            <b-form>
              <b-form-checkbox-group
                id="parameter-to-monitor-checkbox"
                v-model="selectedParametersToMonitor"
              >
                <b-list-group-item v-for="(parameterToMonitor, parameterToMonitorIndex) in qtiJson.parametersToMonitor"
                                   :key="`potential-condition-${ parameterToMonitorIndex}`"
                                   style="font-size:12px"
                                   class="m-1 parameters-to-monitor"
                >
                  <b-form-checkbox :value="parameterToMonitor.identifier">
                    {{ parameterToMonitor.value }}
                  </b-form-checkbox>
                </b-list-group-item>
              </b-form-checkbox-group>
            </b-form>
          </b-list-group>
        </b-card>
      </b-col>
    </b-row>
  </b-container>
</template>

<script>
export default {
  name: 'BowTieViewer',
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    }
  },
  data: () => ({
    selectedActionsToTake: [],
    selectedPotentialCondition: [],
    selectedParametersToMonitor: []
  }),
  watch: {
    selectedActionsToTake: function (newSelectedActionsToTake, oldSelectedActionsToTake) {
      if (newSelectedActionsToTake.length > 2) {
        this.$noty.info('Only 2 Actions To Take should be selected. Please de-select 1 of your choices.')
      }
    },
    selectedPotentialCondition: function (newSelectedPotentialCondition, oldSelectedPotentialCondition) {
      if (newSelectedPotentialCondition.length > 1) {
        this.$noty.info('Only 1 Potential Condition should be selected. Please de-select 1 of your choices.')
      }
    },
    selectedParametersToMonitor: function (newSelectedParametersToMonitor, oldSelectedParametersToMonitor) {
      if (newSelectedParametersToMonitor.length > 2) {
        this.$noty.info('Only 2 Parameters To Monitor should be selected. Please de-select 1 of your choices.')
      }
    }
  }
}
</script>
<style scoped>
.action-to-take {
  background-color: #E7F0FC;
}

.potential-conditions {
  background-color: #C3E9F2;
}

.parameters-to-monitor {
  background-color: #EDF1F5;
}

.bow-tie.list-group-item-header {
  padding: 10px;
  height: 50px;
  background-color: #EDF5F4;
}
</style>
