import React, { useMemo, useState, useEffect } from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import { Line } from "react-chartjs-2";
import {
  Chart as ChartJS,
  LineElement,
  PointElement,
  LinearScale,
  CategoryScale,
  Legend,
  Tooltip,
} from "chart.js";

ChartJS.register(LineElement, PointElement, LinearScale, CategoryScale, Legend, Tooltip);

const num = (v) => (v === "" || isNaN(v) ? 0 : Number(v));

function Currency({ value }) {
  return <span>${value.toLocaleString(undefined, { maximumFractionDigits: 0 })}</span>;
}

function NumberInput({ label, value, setValue, step = "1", min = "0", max, suffix }) {
  return (
    <label className="field">
      <span>{label}</span>
      <input
        type="number"
        value={value}
        step={step}
        min={min}
        max={max}
        onChange={(e) => setValue(e.target.value)}
      />
      {suffix && <span className="suffix">{suffix}</span>}
    </label>
  );
}

function PctInput(p) {
  return <NumberInput {...p} step="0.1" min="0" max="100" suffix="%" />;
}

function Section({ title, children }) {
  return (
    <section className="card">
      <h3>{title}</h3>
      <div className="grid">{children}</div>
    </section>
  );
}

function App() {
  const [age, setAge] = useState(35);
  const [retireAge, setRetireAge] = useState(67);
  const [salary, setSalary] = useState(90000);
  const [currentBalance, setCurrentBalance] = useState(80000);

  const [sgRate, setSgRate] = useState(12); // %
  const [volPreTaxPct, setVolPreTaxPct] = useState(0);
  const [volAfterTaxAnnual, setVolAfterTaxAnnual] = useState(0);

  const [salaryGrowth, setSalaryGrowth] = useState(3.5);
  const [returnNominal, setReturnNominal] = useState(6.5);
  const [earningsTax, setEarningsTax] = useState(7.0);
  const [contribTax, setContribTax] = useState(15.0);
  const [feePct, setFeePct] = useState(0.7);
  const [feeFixed, setFeeFixed] = useState(100);

  const [inflation, setInflation] = useState(2.5);
  const [longevityAge, setLongevityAge] = useState(92);
  const [agePensionPa, setAgePensionPa] = useState(0);

  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const payload = useMemo(() => ({
    age: num(age),
    retireAge: num(retireAge),
    salary: num(salary),
    currentBalance: num(currentBalance),
    sgRate: num(sgRate) / 100,
    volPreTaxPct: num(volPreTaxPct) / 100,
    volAfterTaxAnnual: num(volAfterTaxAnnual),
    salaryGrowth: num(salaryGrowth) / 100,
    returnNominal: num(returnNominal) / 100,
    earningsTax: num(earningsTax) / 100,
    contribTax: num(contribTax) / 100,
    feePct: num(feePct) / 100,
    feeFixed: num(feeFixed),
    inflation: num(inflation) / 100,
    longevityAge: num(longevityAge),
    agePensionPa: num(agePensionPa),
  }), [age, retireAge, salary, currentBalance, sgRate, volPreTaxPct, volAfterTaxAnnual, salaryGrowth, returnNominal, earningsTax, contribTax, feePct, feeFixed, inflation, longevityAge, agePensionPa]);

  async function calculate() {
    setLoading(true);
    setError("");
    try {
      const res = await fetch(WPRC_CFG.restUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data?.error || "Calculation error");
      setResult(data);
    } catch (e) {
      setError(e.message);
      setResult(null);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    calculate();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const balanceChart = useMemo(() => {
    if (!result?.chart?.balanceSeries) return null;
    const labels = result.chart.balanceSeries.map(p => p[0]);
    const data = result.chart.balanceSeries.map(p => p[1]);
    return {
      labels,
      datasets: [{
        label: "Projected Super Balance",
        data,
      }]
    };
  }, [result]);

  return (
    <div className="wprc-app">
      <header className="hero">
        <h2>Retirement Planner</h2>
        <p>Estimate your retirement income and projected super balance. Results are indicative only and shown in today's dollars.</p>
      </header>

      <div className="layout">
        <div className="left">
          <Section title="Basics">
            <NumberInput label="Current age" value={age} setValue={setAge} min="18" max="74" />
            <NumberInput label="Retirement age" value={retireAge} setValue={setRetireAge} min={Number(age)+1} max="75" />
            <NumberInput label="Current salary (annual)" value={salary} setValue={setSalary} step="1000" />
            <NumberInput label="Current super balance" value={currentBalance} setValue={setCurrentBalance} step="1000" />
          </Section>

          <Section title="Contributions">
            <PctInput label="Employer SG rate" value={sgRate} setValue={setSgRate} />
            <PctInput label="Voluntary pre-tax (salary sacrifice)" value={volPreTaxPct} setValue={setVolPreTaxPct} />
            <NumberInput label="Voluntary after-tax (annual)" value={volAfterTaxAnnual} setValue={setVolAfterTaxAnnual} step="500" />
          </Section>

          <Section title="Assumptions">
            <PctInput label="Investment return (nominal p.a.)" value={returnNominal} setValue={setReturnNominal} />
            <PctInput label="Earnings tax (effective)" value={earningsTax} setValue={setEarningsTax} />
            <PctInput label="Contribution tax (employer + pre-tax)" value={contribTax} setValue={setContribTax} />
            <PctInput label="Fees (% of balance)" value={feePct} setValue={setFeePct} />
            <NumberInput label="Fees (fixed $ p.a.)" value={feeFixed} setValue={setFeeFixed} />
            <PctInput label="Salary growth" value={salaryGrowth} setValue={setSalaryGrowth} />
            <PctInput label="Inflation (for today's dollars)" value={inflation} setValue={setInflation} />
            <NumberInput label="Plan to age" value={longevityAge} setValue={setLongevityAge} min={Number(retireAge)+1} max="110" />
            <NumberInput label="Expected Age Pension (annual)" value={agePensionPa} setValue={setAgePensionPa} step="500" />
          </Section>

          <div className="actions">
            <button onClick={calculate} disabled={loading}>{loading ? "Calculating..." : "Update results"}</button>
          </div>
          {error && <div className="error">{error}</div>}
        </div>

        <div className="right">
          <section className="card">
            <h3>Results</h3>
            {result ? (
              <div className="results">
                <div className="kpis">
                  <div className="kpi">
                    <span className="label">Projected balance at {result.atRetirement.age}</span>
                    <strong><Currency value={result.atRetirement.balance} /></strong>
                  </div>
                  <div className="kpi">
                    <span className="label">Annual income from super (today's $)</span>
                    <strong><Currency value={result.income.annualSuperIncomeToday} /></strong>
                  </div>
                  <div className="kpi">
                    <span className="label">Expected Age Pension (today's $)</span>
                    <strong><Currency value={result.income.annualAgePensionToday} /></strong>
                  </div>
                  <div className="kpi">
                    <span className="label">Estimated total annual income</span>
                    <strong><Currency value={result.income.annualTotalIncomeToday} /></strong>
                  </div>
                </div>
                {balanceChart && (
                  <div className="chart">
                    <Line data={balanceChart} options={{
                      responsive: true,
                      maintainAspectRatio: false,
                      scales: {
                        y: { ticks: { callback: (v) => '$' + Number(v).toLocaleString() } }
                      }
                    }} />
                  </div>
                )}
                <p className="disclaimer">
                  This tool is for general information only and does not consider your personal objectives, financial situation or needs. Consider seeking independent advice.
                </p>
              </div>
            ) : (
              <p>Enter your details and click "Update results".</p>
            )}
          </section>
        </div>
      </div>
    </div>
  );
}

function mount() {
  const el = document.getElementById("retcalc-root");
  if (!el) return;
  const root = createRoot(el);
  root.render(<App />);
}

document.addEventListener("DOMContentLoaded", mount);
