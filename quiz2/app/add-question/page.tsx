"use client"
import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"

export default function AddQuestion({ params }: any) {
  const quizId = params.quizId
  const [question, setQuestion] = useState("")
  const [options, setOptions] = useState(["","","",""])
  const [correct, setCorrect] = useState("A")

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    await fetch("http://localhost/backend/add_question.php", {
      method: "POST",
      headers: {"Content-Type": "application/json"},
      body: JSON.stringify({ quiz_id: quizId, question, options, correct })
    })
    alert("Question Added!")
  }

  return (
    <div className="flex justify-center p-6">
      <Card className="w-full max-w-md shadow-lg">
        <CardHeader>
          <CardTitle>Add Question</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            <Input placeholder="Question" value={question} onChange={(e)=>setQuestion(e.target.value)} required/>
            {["A","B","C","D"].map((opt,i)=>(
              <Input key={i} placeholder={`Option ${opt}`} value={options[i]} onChange={(e)=>{
                const newOptions=[...options]
                newOptions[i]=e.target.value
                setOptions(newOptions)
              }} required/>
            ))}
            <Select onValueChange={setCorrect} defaultValue="A">
              <SelectTrigger><SelectValue placeholder="Correct Option"/></SelectTrigger>
              <SelectContent>
                <SelectItem value="A">A</SelectItem>
                <SelectItem value="B">B</SelectItem>
                <SelectItem value="C">C</SelectItem>
                <SelectItem value="D">D</SelectItem>
              </SelectContent>
            </Select>
            <Button type="submit" className="w-full">Save Question</Button>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
