@php
  $adminUsersData = $adminUsersData ?? app(\Modules\Discovery\app\Services\AdminUsersTableService::class)->build();
  $adminStudentRows = $adminUsersData['rows'];
  $adminUserProgramOptions = $adminUsersData['program_options'];
  $adminUserInstitutionOptions = $adminUsersData['institution_options'];

  $adminMentorsData = $adminMentorsData ?? app(\Modules\Discovery\app\Services\AdminMentorsTableService::class)->build();
  $adminMentorRows = $adminMentorsData['rows'];
  $adminMentorProgramOptions = $adminMentorsData['program_options'];
  $adminMentorStatusOptions = $adminMentorsData['status_options'];

  $adminServiceRows = $adminServiceRows ?? app(\Modules\Discovery\app\Services\AdminServicesTableService::class)->build();

  $formatAdminMoney = static function ($amount): string {
    if ($amount === null) {
      return '-';
    }

    $precision = fmod((float) $amount, 1.0) === 0.0 ? 0 : 2;

    return '$' . number_format((float) $amount, $precision);
  };
@endphp

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Grads Paths Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <!-- <link rel="stylesheet" href="demo14.css" /> -->
    <link rel="stylesheet" href="{{ asset('assets/css/demo12.css') }}" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  </head>

  <body>
    <div class="app">
      <section class="dashboard" id="dashboard">
        <div class="sidebar-overlay" id="adminSidebarOverlay"></div>
        <aside class="sidebar">
          <div class="brand">
            <div class="brand-icon">GP</div>
            <div>
              <h2>Grads Paths</h2>
              <p>Admin Dashboard</p>
            </div>
          </div>

          <nav class="nav">
            <button class="nav-link active" data-tab="overview">
              Overview
            </button>
            <button class="nav-link" data-tab="users">Users</button>
            <button class="nav-link" data-tab="mentors">Mentors</button>
            <button class="nav-link" data-tab="services">Services</button>
            <button class="nav-link" data-tab="revenue">Revenue</button>
            <button class="nav-link" data-tab="rankings">Rankings</button>
            <button class="nav-link" data-tab="manual">Manual Actions</button>
          </nav>

          <div class="sidebar-bottom">
            <button class="ghost-btn" id="reloadBtn">Reload</button>
            <form method="POST" action="{{ route('auth.logout') }}">
              @csrf
              <button class="ghost-btn" id="signOutBtn" type="submit">Sign out</button>
            </form>
          </div>
        </aside>

        <main class="main">
          <header class="topbar">
            <div class="topbar-row">
              <div class="topbar-left">
                <button class="mobile-menu-toggle" id="adminMenuToggle" type="button">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                  </svg>
                </button>
                <h1>Admin Dashboard</h1>
              </div>
              <div class="status-pill">Live</div>
            </div>
            <p class="topbar-desc">
              Track users, mentors, service mix, meeting sizes, schools, and revenue.
            </p>
          </header>

          <!-- OVERVIEW -->
          <section class="tab-panel active" id="overview">
            <div class="kpi-grid">
              <div class="kpi-card">
                <span>Total Users</span>
                <strong>248</strong>
                <small>34 new in 30 days</small>
              </div>
              <div class="kpi-card">
                <span>Active Mentors</span>
                <strong>36</strong>
                <small>6 inactive</small>
              </div>
              <div class="kpi-card">
                <span>Bookings (30d)</span>
                <strong>96</strong>
                <small>12 this week</small>
              </div>
              <div class="kpi-card">
                <span>Gross Revenue</span>
                <strong>$12,840</strong>
                <small>30 day total</small>
              </div>
              <div class="kpi-card">
                <span>Platform Revenue</span>
                <strong>$4,118</strong>
                <small>after mentor split</small>
              </div>
              <div class="kpi-card">
                <span>Refunds</span>
                <strong>$420</strong>
                <small>5 requests</small>
              </div>
            </div>

            <div class="chart-grid">
              <div class="panel chart-panel">
                <div class="panel-head">
                  <h3>Bookings Over Time</h3>
                  <span>Last 6 months</span>
                </div>
                <canvas id="bookingsChart"></canvas>
              </div>

              <div class="panel chart-panel">
                <div class="panel-head">
                  <h3>Revenue Over Time</h3>
                  <span>Last 6 months</span>
                </div>
                <canvas id="revenueChart"></canvas>
              </div>
            </div>

            <div class="split-grid">
              <div class="panel">
                <div class="panel-head">
                  <h3>Top Mentors</h3>
                  <span>by revenue</span>
                </div>
                <div class="table-wrap compact-table">
                  <table>
                    <thead>
                      <tr>
                        <th>Mentor</th>
                        <th>Program</th>
                        <th>Meetings</th>
                        <th>Revenue</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>Sarah Kim</td>
                        <td>MBA</td>
                        <td>22</td>
                        <td>$1,870</td>
                      </tr>
                      <tr>
                        <td>Daniel Brooks</td>
                        <td>Law</td>
                        <td>17</td>
                        <td>$1,190</td>
                      </tr>
                      <tr>
                        <td>Leah Morris</td>
                        <td>CMHC</td>
                        <td>12</td>
                        <td>$720</td>
                      </tr>
                      <tr>
                        <td>Rachel Adams</td>
                        <td>MBA</td>
                        <td>10</td>
                        <td>$790</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="panel">
                <div class="panel-head">
                  <h3>Top Services</h3>
                  <span>by bookings</span>
                </div>
                <div class="table-wrap compact-table">
                  <table>
                    <thead>
                      <tr>
                        <th>Service</th>
                        <th>Bookings</th>
                        <th>Revenue</th>
                        <th>Set Price</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>Program Insights</td>
                        <td>31</td>
                        <td>$2,480</td>
                        <td>See formats</td>
                      </tr>
                      <tr>
                        <td>Interview Prep</td>
                        <td>24</td>
                        <td>$1,920</td>
                        <td>See formats</td>
                      </tr>
                      <tr>
                        <td>Application Review</td>
                        <td>18</td>
                        <td>$1,080</td>
                        <td>$60</td>
                      </tr>
                      <tr>
                        <td>Office Hours</td>
                        <td>14</td>
                        <td>$560</td>
                        <td>$200 subscription</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </section>

          <!-- USERS -->
          <section class="tab-panel" id="users">
            <div class="section-head">
              <div>
                <h2>Users</h2>
                <p>
                  Total meetings, total spend, and numeric breakdown by service.
                </p>
              </div>
              <button class="primary-btn">Export Users</button>
            </div>

            <div class="toolbar">
              <input
                type="text"
                id="usersSearch"
                placeholder="Search user, email, institution..."
              />
              <select id="usersProgramFilter">
                <option value="all">All Programs</option>
                @foreach ($adminUserProgramOptions as $adminUserProgramOption)
                  <option value="{{ $adminUserProgramOption }}">{{ $adminUserProgramOption }}</option>
                @endforeach
              </select>
              <select id="usersInstitutionFilter">
                <option value="all">All Institutions</option>
                @foreach ($adminUserInstitutionOptions as $adminUserInstitutionOption)
                  <option value="{{ $adminUserInstitutionOption }}">{{ $adminUserInstitutionOption }}</option>
                @endforeach
              </select>
            </div>

            <div class="table-wrap panel no-pad">
              <table id="usersTable">
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Program</th>
                    <th>Institution</th>
                    <th>Total Meetings</th>
                    <th>Total Spent</th>
                    <th>Free Consult</th>
                    <th>Tutoring</th>
                    <th>Program Insights</th>
                    <th>Interview Prep</th>
                    <th>Application Review</th>
                    <th>Gap Year Planning</th>
                    <th>Office Hours</th>
                    <th>Last Active</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($adminStudentRows as $adminStudent)
                    <tr data-program="{{ $adminStudent['program_filter'] }}" data-institution="{{ $adminStudent['institution_filter'] }}">
                      <td>
                        <strong>{{ $adminStudent['name'] ?? '-' }}</strong><span>{{ $adminStudent['email'] ?? '-' }}</span>
                      </td>
                      <td>{{ $adminStudent['program'] ?: '-' }}</td>
                      <td>{{ $adminStudent['institution'] ?: '-' }}</td>
                      <td>{{ $adminStudent['total_meetings'] }}</td>
                      <td>{{ $formatAdminMoney($adminStudent['total_spent']) }}</td>
                      <td>{{ $adminStudent['free_consult'] }}</td>
                      <td>{{ $adminStudent['tutoring'] }}</td>
                      <td>{{ $adminStudent['program_insights'] }}</td>
                      <td>{{ $adminStudent['interview_prep'] }}</td>
                      <td>{{ $adminStudent['application_review'] }}</td>
                      <td>{{ $adminStudent['gap_year_planning'] }}</td>
                      <td>{{ $adminStudent['office_hours'] }}</td>
                      <td>{{ $adminStudent['last_active'] ?: '-' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </section>

          <!-- MENTORS -->
          <section class="tab-panel" id="mentors">
            <div class="section-head">
              <div>
                <h2>Mentors</h2>
                <p>Includes full service-count breakdown and totals.</p>
              </div>
              <button class="primary-btn">Export Mentors</button>
            </div>

            <div class="toolbar">
              <input
                type="text"
                id="mentorsSearch"
                placeholder="Search mentor, school, email..."
              />
              <select id="mentorsProgramFilter">
                <option value="all">All Programs</option>
                @foreach ($adminMentorProgramOptions as $adminMentorProgramOption)
                  <option value="{{ $adminMentorProgramOption }}">{{ $adminMentorProgramOption }}</option>
                @endforeach
              </select>
              <select id="mentorsStatusFilter">
                <option value="all">All Statuses</option>
                @foreach ($adminMentorStatusOptions as $adminMentorStatusOption)
                  <option value="{{ $adminMentorStatusOption }}">{{ $adminMentorStatusOption }}</option>
                @endforeach
              </select>
            </div>

            <div class="table-wrap panel no-pad">
              <table id="mentorsTable">
                <thead>
                  <tr>
                    <th>Mentor</th>
                    <th>Program</th>
                    <th>School</th>
                    <th>Total Meetings</th>
                    <th>Total Revenue</th>
                    <th>Free Consult</th>
                    <th>Tutoring</th>
                    <th>Program Insights</th>
                    <th>Interview Prep</th>
                    <th>Application Review</th>
                    <th>Gap Year Planning</th>
                    <th>Office Hours</th>
                    <th>Missed</th>
                    <th>Refunds</th>
                    <th>Rating</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($adminMentorRows as $adminMentor)
                    <tr data-program="{{ $adminMentor['program_filter'] }}" data-status="{{ $adminMentor['status_filter'] }}">
                      <td>
                        <strong>{{ $adminMentor['name'] ?: '-' }}</strong><span>{{ $adminMentor['email'] ?: '-' }}</span>
                      </td>
                      <td>{{ $adminMentor['program'] ?: '-' }}</td>
                      <td>{{ $adminMentor['school'] ?: '-' }}</td>
                      <td>{{ $adminMentor['total_meetings'] }}</td>
                      <td>{{ $formatAdminMoney($adminMentor['total_revenue']) }}</td>
                      <td>{{ $adminMentor['free_consult'] }}</td>
                      <td>{{ $adminMentor['tutoring'] }}</td>
                      <td>{{ $adminMentor['program_insights'] }}</td>
                      <td>{{ $adminMentor['interview_prep'] }}</td>
                      <td>{{ $adminMentor['application_review'] }}</td>
                      <td>{{ $adminMentor['gap_year_planning'] }}</td>
                      <td>{{ $adminMentor['office_hours'] }}</td>
                      <td>{{ $adminMentor['missed'] }}</td>
                      <td>{{ $adminMentor['refunds'] }}</td>
                      <td>{{ $adminMentor['rating'] ?: '-' }}</td>
                      <td>{{ $adminMentor['status'] ?: '-' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </section>

          <!-- SERVICES -->
          <section class="tab-panel" id="services">
            <div class="section-head">
              <div>
                <h2>Services</h2>
                <p>
                  Fixed service pricing, meeting-size breakdown, and service
                  performance.
                </p>
              </div>
              <button class="primary-btn">Export Services</button>
            </div>

            @if (session('success'))
              <div class="panel" style="margin-bottom: 20px; border: 1px solid #d4f2df; background: #f3fff7; color: #1d6b3d;">
                <strong>{{ session('success') }}</strong>
              </div>
            @endif

            <div class="table-wrap panel no-pad">
              <table id="servicesTable">
                <thead>
                  <tr>
                    <th>Service</th>
                    <th>Format</th>
                    <th>Set Price</th>
                    <th>Bookings</th>
                    <th>Revenue</th>
                    <th>Mentors Offering</th>
                    <th>Popularity Rank</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($adminServiceRows as $adminService)
                    <tr>
                      <td><strong>{{ $adminService['service_name'] ?: '-' }}</strong></td>
                      <td>{{ $adminService['format'] ?: '-' }}</td>
                      <td>{{ $adminService['set_price'] ?: '-' }}</td>
                      <td>{{ $adminService['bookings'] }}</td>
                      <td>{{ $formatAdminMoney($adminService['revenue']) }}</td>
                      <td>{{ $adminService['mentors_offering'] }}</td>
                      <td>{{ $adminService['popularity_rank'] }}</td>
                    </tr>
                  @endforeach
                  @if (false)
                  <tr>
                    <td><strong>Free Consultation</strong></td>
                    <td>Intro call</td>
                    <td>$0</td>
                    <td>22</td>
                    <td>$0</td>
                    <td>15</td>
                    <td>Awareness</td>
                  </tr>
                  <tr>
                    <td><strong>Tutoring</strong></td>
                    <td>60 min • 1:1 / 1:3 / 1:5</td>
                    <td>
                      1:1 $70 • 1:3 $62.99 pp / $188.98 total • 1:5 $55.99 pp /
                      $279.97 total
                    </td>
                    <td>8</td>
                    <td>$640</td>
                    <td>5</td>
                    <td>6</td>
                  </tr>
                  <tr>
                    <td><strong>Program Insights</strong></td>
                    <td>60 min • 1:1 / 1:3 / 1:5</td>
                    <td>
                      1:1 $65 • 1:3 $58.49 pp / $175.48 total • 1:5 $51.99 pp /
                      $259.97 total
                    </td>
                    <td>31</td>
                    <td>$2,480</td>
                    <td>12</td>
                    <td>1</td>
                  </tr>
                  <tr>
                    <td><strong>Interview Prep</strong></td>
                    <td>60 min • 1:1 / 1:3 / 1:5</td>
                    <td>
                      1:1 $65 • 1:3 $58.49 pp / $175.48 total • 1:5 $51.99 pp /
                      $259.97 total
                    </td>
                    <td>24</td>
                    <td>$1,920</td>
                    <td>10</td>
                    <td>2</td>
                  </tr>
                  <tr>
                    <td><strong>Application Review</strong></td>
                    <td>60 min • 1:1</td>
                    <td>$60</td>
                    <td>18</td>
                    <td>$1,080</td>
                    <td>8</td>
                    <td>3</td>
                  </tr>
                  <tr>
                    <td><strong>Gap Year Planning</strong></td>
                    <td>60 min • 1:1</td>
                    <td>$50</td>
                    <td>6</td>
                    <td>$300</td>
                    <td>4</td>
                    <td>7</td>
                  </tr>
                  <tr>
                    <td><strong>Office Hours</strong></td>
                    <td>
                      45 min subscription • 1 through 5 • 5 credits / 5 meetings
                    </td>
                    <td>$200 subscription</td>
                    <td>14</td>
                    <td>$560</td>
                    <td>7</td>
                    <td>4</td>
                  </tr>
                  @endif
                </tbody>
              </table>
            </div>

            <div class="chart-grid single-chart">
              <div class="panel chart-panel">
                <div class="panel-head">
                  <h3>Service Popularity</h3>
                  <span>by bookings</span>
                </div>
                <canvas id="servicesChart"></canvas>
              </div>
            </div>
          </section>

          <!-- REVENUE -->
          <section class="tab-panel" id="revenue">
            <div class="section-head">
              <div>
                <h2>Revenue</h2>
                <p>Programs, top mentors, and fixed service pricing model.</p>
              </div>
              <button class="primary-btn">Export Revenue</button>
            </div>

            <div class="summary-grid">
              <div class="small-card">
                <span>Gross Revenue</span>
                <strong>$12,840</strong>
                <small>30 days</small>
              </div>
              <div class="small-card">
                <span>Mentor Payouts</span>
                <strong>$8,722</strong>
                <small>paid / due</small>
              </div>
              <div class="small-card">
                <span>Platform Revenue</span>
                <strong>$4,118</strong>
                <small>retained amount</small>
              </div>
              <div class="small-card">
                <span>Refund Amount</span>
                <strong>$420</strong>
                <small>current period</small>
              </div>
            </div>

            <div class="chart-grid">
              <div class="panel chart-panel">
                <div class="panel-head">
                  <h3>Revenue by Program</h3>
                  <span>all active programs</span>
                </div>
                <canvas id="programRevenueChart"></canvas>
              </div>

              <div class="panel chart-panel">
                <div class="panel-head">
                  <h3>Top Mentors by Revenue</h3>
                  <span>current period</span>
                </div>
                <canvas id="topMentorsChart"></canvas>
              </div>
            </div>
          </section>

          <!-- RANKINGS -->
          <section class="tab-panel" id="rankings">
            <div class="section-head">
              <div>
                <h2>Rankings</h2>
                <p>
                  All programs, all services, and top 5 most booked schools.
                </p>
              </div>
            </div>

            <div class="rank-grid">
              <div class="panel">
                <div class="panel-head">
                  <h3>Programs by Rank</h3>
                </div>
                <div class="mini-list">
                  <div class="mini-item compact">
                    <strong>1. MBA</strong><span>46 bookings</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>2. Law</strong><span>29 bookings</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>3. CMHC</strong><span>21 bookings</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>4. MSW</strong><span>15 bookings</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>5. Clinical Psy</strong><span>11 bookings</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>6. MFT</strong><span>8 bookings</span>
                  </div>
                </div>
              </div>

              <div class="panel">
                <div class="panel-head">
                  <h3>Services by Rank</h3>
                </div>
                <div class="mini-list">
                  <div class="mini-item compact">
                    <strong>1. Program Insights</strong><span>31 bookings</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>2. Interview Prep</strong><span>24 bookings</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>3. Free Consultation</strong><span>22 uses</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>4. Application Review</strong
                    ><span>18 bookings</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>5. Office Hours</strong><span>14 bookings</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>6. Tutoring</strong><span>8 bookings</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>7. Gap Year Planning</strong><span>6 bookings</span>
                  </div>
                </div>
              </div>

              <div class="panel">
                <div class="panel-head">
                  <h3>Top 5 Most Booked Schools</h3>
                </div>
                <div class="mini-list">
                  <div class="mini-item compact">
                    <strong>1. Harvard</strong><span>Top demand</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>2. Yale</strong><span>Strong</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>3. NYU</strong><span>Strong</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>4. Stanford</strong><span>Growing</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>5. Columbia</strong><span>Growing</span>
                  </div>
                </div>
              </div>

              <div class="panel">
                <div class="panel-head">
                  <h3>Meeting Size Mix</h3>
                </div>
                <div class="mini-list">
                  <div class="mini-item compact">
                    <strong>1 on 1</strong><span>Most common</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>1 on 3</strong><span>Secondary</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>1 on 5</strong><span>Lower usage</span>
                  </div>
                  <div class="mini-item compact">
                    <strong>Office Hours Credits</strong
                    ><span>Subscription-based</span>
                  </div>
                </div>
              </div>
            </div>
          </section>

          @include('discovery::admin.manualaction')
        </main>
      </section>
    </div>

    <script src="{{ asset('assets/js/demo12.js') }}"></script>
    <!-- <script src="demo14.js"></script> -->
  </body>
</html>
