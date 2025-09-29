"use client"
import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"

export default function CreateQuiz() {
  const [title, setTitle] = useState("")
  const [desc, setDesc] = useState("")

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    const res = await fetch("http://localhost/backend/create_quiz.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ title, desc, user_id: 1 })
    })
    const data = await res.json()
    alert(`Quiz Created! Quiz ID: ${data.quiz_id}`)
  }

  return (
    <div className="flex justify-center p-6">
      <Card className="w-full max-w-md shadow-xl">
        <CardHeader>
          <CardTitle>Create Your Quiz</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            <Input placeholder="Quiz Title" value={title} onChange={(e)=>setTitle(e.target.value)} required/>
            <Textarea placeholder="Description" value={desc} onChange={(e)=>setDesc(e.target.value)}/>
            <Button type="submit" className="w-full">Create Quiz</Button>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
