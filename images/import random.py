import random
import string

def generate_password(length):
    # Определяем набор символов для генерации пароля
    characters = (
        string.ascii_letters +   # Буквы разных регистров
        string.digits +          # Цифры
        string.punctuation       # Специальные символы
    )
    
    # Генератор пароля заданной длины
    password = ''.join(random.choice(characters) for _ in range(length))
    
    return password

# Пример использования:
password_length = int(input("Введите желаемую длину пароля: "))
generated_password = generate_password(password_length)
print("f"Сгенерированный пароль: {generated_passw}"")