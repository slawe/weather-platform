const state = {
  comparison: null,
  selectedLocationId: null,
};

const elements = {
  connectionDot: document.querySelector('#connectionDot'),
  connectionLabel: document.querySelector('#connectionLabel'),
  locationCount: document.querySelector('#locationCount'),
  sourceCount: document.querySelector('#sourceCount'),
  maxTemperatureGap: document.querySelector('#maxTemperatureGap'),
  updatedAt: document.querySelector('#updatedAt'),
  overviewView: document.querySelector('#overviewView'),
  detailView: document.querySelector('#detailView'),
  detailCity: document.querySelector('#detailCity'),
  backButton: document.querySelector('#backButton'),
  comparisonGrid: document.querySelector('#comparisonGrid'),
  locationTemplate: document.querySelector('#locationTemplate'),
  weeklyHistory: document.querySelector('#weeklyHistory'),
  monthlyHistory: document.querySelector('#monthlyHistory'),
  quarterlyHistory: document.querySelector('#quarterlyHistory'),
  weeklyCount: document.querySelector('#weeklyCount'),
  monthlyCount: document.querySelector('#monthlyCount'),
  quarterlyCount: document.querySelector('#quarterlyCount'),
};

initialize();

async function initialize() {
  setConnectionState('Connecting', 'pending');
  elements.backButton.addEventListener('click', showOverview);

  try {
    const response = await fetch('/api/comparison/current');

    if (!response.ok) {
      throw new Error(`Initial comparison request failed with status ${response.status}.`);
    }

    updateDashboard(await response.json());
    setConnectionState('Live', 'live');
  } catch (error) {
    setConnectionState('Offline', 'error');
  }

  connectSocket();
}

function connectSocket() {
  const socket = io();

  socket.on('connect', () => {
    setConnectionState('Live', 'live');
  });

  socket.on('disconnect', () => {
    setConnectionState('Reconnecting', 'pending');
  });

  socket.on('connect_error', () => {
    setConnectionState('Offline', 'error');
  });

  socket.on('weather.comparison.updated', (comparison) => {
    updateDashboard(comparison);
  });
}

function updateDashboard(comparison) {
  state.comparison = comparison;
  renderSummary(comparison);
  renderLocations(comparison.locations ?? []);

  if (state.selectedLocationId !== null) {
    renderSelectedLocationHeader();
    loadHistory();
  }
}

function renderSummary(comparison) {
  const locations = comparison.locations ?? [];
  const uniqueSources = new Set(
    locations.flatMap((location) => location.sources.map((source) => source.source)),
  );
  const maxGap = locations.reduce((currentMax, location) => {
    const gap = location.difference?.temperatureDifferenceC ?? 0;

    return Math.max(currentMax, gap);
  }, 0);

  elements.locationCount.textContent = String(locations.length);
  elements.sourceCount.textContent = String(uniqueSources.size);
  elements.maxTemperatureGap.textContent = `${maxGap.toFixed(1)} C`;
  elements.updatedAt.textContent = formatTime(comparison.generatedAt);
}

function renderLocations(locations) {
  elements.comparisonGrid.replaceChildren();

  if (locations.length === 0) {
    const emptyState = document.createElement('div');
    emptyState.className = 'empty-state';
    emptyState.textContent = 'No weather comparison data available.';
    elements.comparisonGrid.append(emptyState);

    return;
  }

  for (const location of locations) {
    elements.comparisonGrid.append(renderLocation(location));
  }
}

function renderLocation(location) {
  const fragment = elements.locationTemplate.content.cloneNode(true);
  const card = fragment.querySelector('.location-card');
  const cityButton = card.querySelector('[data-field="city"]');

  cityButton.textContent = location.city;
  cityButton.addEventListener('click', () => showLocationHistory(location.locationId));
  card.querySelector('[data-field="country"]').textContent = location.country;
  card.querySelector('[data-field="sourceCount"]').textContent = `${location.sources.length} sources`;

  const sourceList = card.querySelector('[data-field="sources"]');
  sourceList.replaceChildren(...location.sources.map(renderSource));

  const differencePanel = card.querySelector('[data-field="difference"]');
  differencePanel.replaceChildren(...renderDifference(location.difference));

  return card;
}

function renderSource(source) {
  const row = document.createElement('div');
  row.className = 'source-row';

  const sourceInfo = document.createElement('div');

  const sourceName = document.createElement('div');
  sourceName.className = 'source-name';
  sourceName.textContent = source.source;

  const observedAt = document.createElement('div');
  observedAt.className = 'source-observed';
  observedAt.textContent = formatTime(source.observedAt);

  sourceInfo.append(sourceName, observedAt);

  const values = document.createElement('div');
  values.className = 'weather-values';
  values.append(
    valuePill(`${source.temperatureC.toFixed(1)} C`),
    valuePill(`${source.windSpeedKmh.toFixed(1)} km/h`),
    valuePill(`Code ${source.weatherCode}`),
  );

  row.append(sourceInfo, values);

  return row;
}

function renderDifference(difference) {
  const title = document.createElement('div');
  title.className = 'difference-title';
  title.append(textNode('Difference'), textNode(difference ? `${difference.sourceA} vs ${difference.sourceB}` : 'Waiting'));

  if (!difference) {
    const empty = document.createElement('div');
    empty.className = 'source-observed';
    empty.textContent = 'Single source available.';

    return [title, empty];
  }

  return [
    title,
    gapRow('Temp', difference.temperatureDifferenceC, 'C', 12),
    gapRow('Wind', difference.windSpeedDifferenceKmh, 'km/h', 20),
    gapRow('Code', difference.weatherCodeDifferent ? 1 : 0, '', 1),
  ];
}

function gapRow(label, value, unit, maxValue) {
  const row = document.createElement('div');
  row.className = 'gap-row';

  const labelElement = document.createElement('span');
  labelElement.textContent = label;

  const track = document.createElement('div');
  track.className = 'gap-track';

  const fill = document.createElement('div');
  fill.className = 'gap-fill';
  fill.style.width = `${Math.min(100, (value / maxValue) * 100)}%`;
  track.append(fill);

  const valueElement = document.createElement('span');
  valueElement.textContent = unit ? `${value.toFixed(1)} ${unit}` : value === 1 ? 'diff' : 'same';

  row.append(labelElement, track, valueElement);

  return row;
}

function valuePill(value) {
  const element = document.createElement('span');
  element.className = 'value-pill';
  element.textContent = value;

  return element;
}

function textNode(value) {
  const element = document.createElement('span');
  element.textContent = value;

  return element;
}

function setConnectionState(label, stateName) {
  elements.connectionLabel.textContent = label;
  elements.connectionDot.classList.toggle('is-live', stateName === 'live');
  elements.connectionDot.classList.toggle('is-error', stateName === 'error');
}

function showLocationHistory(locationId) {
  state.selectedLocationId = locationId;
  elements.overviewView.classList.add('is-hidden');
  elements.detailView.classList.remove('is-hidden');
  renderSelectedLocationHeader();
  loadHistory();
}

function showOverview() {
  state.selectedLocationId = null;
  elements.detailView.classList.add('is-hidden');
  elements.overviewView.classList.remove('is-hidden');
}

function renderSelectedLocationHeader() {
  const location = findSelectedLocation();
  elements.detailCity.textContent = location
    ? `${location.city}, ${location.country}`
    : 'Location Trends';
}

async function loadHistory() {
  if (!state.selectedLocationId) {
    renderHistoryGroups({ weekly: [], monthly: [], quarterly: [] });

    return;
  }

  const params = new URLSearchParams({
    locationId: state.selectedLocationId,
    from: dateDaysAgo(90),
  });

  try {
    const response = await fetch(`/api/comparison/history?${params.toString()}`);

    if (!response.ok) {
      throw new Error(`History request failed with status ${response.status}.`);
    }

    renderHistoryGroups(await response.json());
  } catch (error) {
    renderHistoryError();
  }
}

function findSelectedLocation() {
  return (state.comparison?.locations ?? []).find(
    (location) => location.locationId === state.selectedLocationId,
  );
}

function renderHistoryGroups(history) {
  renderAverageList(elements.weeklyHistory, elements.weeklyCount, history.weekly ?? [], 'weekly');
  renderAverageList(elements.monthlyHistory, elements.monthlyCount, history.monthly ?? [], 'monthly');
  renderAverageList(
    elements.quarterlyHistory,
    elements.quarterlyCount,
    history.quarterly ?? [],
    'quarterly',
  );
}

function renderAverageList(container, counter, averages, periodType) {
  container.replaceChildren();
  counter.textContent = `${countUniquePeriods(averages)} periods`;

  if (averages.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'history-empty';
    empty.textContent = 'No history yet.';
    container.append(empty);

    return;
  }

  const maxTemperature = Math.max(...averages.map((average) => average.averageTemperatureC), 1);

  for (const average of averages) {
    container.append(renderAverageRow(average, maxTemperature, periodType));
  }
}

function renderAverageRow(average, maxTemperature, periodType) {
  const row = document.createElement('div');
  row.className = 'history-row';

  const label = document.createElement('div');
  label.className = 'history-row-label';

  const period = document.createElement('strong');
  period.textContent = formatPeriod(average.periodStart, periodType);

  const source = document.createElement('span');
  source.textContent = average.source;

  label.append(period, source);

  const track = document.createElement('div');
  track.className = 'history-track';

  const fill = document.createElement('div');
  fill.className = 'history-fill';
  fill.style.width = `${Math.min(100, (average.averageTemperatureC / maxTemperature) * 100)}%`;
  track.append(fill);

  const value = document.createElement('div');
  value.className = 'history-value';
  value.textContent = `${average.averageTemperatureC.toFixed(1)} C`;

  row.append(label, track, value);

  return row;
}

function renderHistoryError() {
  const errorState = { weekly: [], monthly: [], quarterly: [] };
  renderHistoryGroups(errorState);

  for (const container of [
    elements.weeklyHistory,
    elements.monthlyHistory,
    elements.quarterlyHistory,
  ]) {
    container.replaceChildren();
    const error = document.createElement('div');
    error.className = 'history-empty';
    error.textContent = 'History unavailable.';
    container.append(error);
  }
}

function countUniquePeriods(averages) {
  return new Set(averages.map((average) => average.periodStart)).size;
}

function dateDaysAgo(days) {
  const date = new Date();
  date.setDate(date.getDate() - days);

  return date.toISOString();
}

function formatPeriod(value, periodType) {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return '-';
  }

  if (periodType === 'quarterly') {
    const quarter = Math.floor(date.getMonth() / 3) + 1;

    return `Q${quarter} ${date.getFullYear()}`;
  }

  if (periodType === 'monthly') {
    return new Intl.DateTimeFormat('en', {
      month: 'short',
      year: 'numeric',
    }).format(date);
  }

  return new Intl.DateTimeFormat('en', {
    month: 'short',
    day: '2-digit',
  }).format(date);
}

function formatTime(value) {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return '-';
  }

  return new Intl.DateTimeFormat('en', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  }).format(date);
}
