"use client"
import { useEffect, useState } from "react"
import { Button } from "@/components/ui/button"

export default function QuizPage({ params }: any){
  const quizId = params.id
  const [quiz, setQuiz] = useState<any>(null)
  const [answers, setAnswers] = useState<any>({})
  const [score, setScore] = useState<number|null>(null)

  useEffect(()=>{
    fetch(`http://localhost/backend/get_quiz.php?quiz_id=${quizId}`)
    .then(res=>res.json())
    .then(data=>setQuiz(data))
  },[quizId])

  const handleSubmit = async ()=>{
    const res = await fetch("http://localhost/backend/submit_quiz.php", {
      method: "POST",
      headers: {"Content-Type":"application/json"},
      body: JSON.stringify({ quiz_id: quizId, user_id:1, answers })
    })
    const data = await res.json()
    setScore(data.score)
  }

  if(!quiz) return <p>Loading...</p>

  return (
    <div className="p-6 max-w-3xl mx-auto space-y-6">
      <h2 className="text-2xl font-bold">{quiz.quiz.title}</h2>
      <p>{quiz.quiz.description}</p>
      {score!==null ? <p className="text-xl font-semibold">Your Score: {score}</p> :
      quiz.questions.map((q:any)=>(
        <div key={q.id} className="p-4 border rounded-md space-y-2">
          <p>{q.question}</p>
          {["A","B","C","D"].map(opt=>(
            <label key={opt} className="block">
              <input type="radio" name={`q${q.id}`} value={opt} onChange={()=>setAnswers({...answers,[q.id]:opt})}/>
              {opt}: {q[`option_${opt.toLowerCase()}`]}
            </label>
          ))}
        </div>
      ))}
      {score===null && <Button onClick={handleSubmit}>Submit Quiz</Button>}
    </div>
  )
}
