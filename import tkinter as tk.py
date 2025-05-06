import tkinter as tk

# Создаем главное окно
root = tk.Tk()
root.title('Простое приложение')

# Первая кнопка выводящая сообщение 'Привет'
def say_hello():
    message_label.config(text="Привет!")

button_hello = tk.Button(root, text='Привет', command=say_hello)
button_hello.pack(pady=10)

# Вторая кнопка закрывающая окно
def close_window():
    root.destroy()

button_close = tk.Button(root, text='Закрыть', command=close_window)
button_close.pack(pady=10)

# Метка для вывода сообщений
message_label = tk.Label(root, text='')
message_label.pack()

# Запускаем главный цикл обработки событий
root.mainloop()