# 仪器控用户管理

## 接口设计

namespace `YiQiKong/User`

### 注册新用户

#### 调用函数

`YiQiKong/User/Signup(object data)`

#### 参数列表

```
	data    object    具体信息, 结构如下:

	{
		"name":                  // 姓名   必填
		"email":                 // 邮箱   必填
		"phone":                 // 电话   必填
		"password":              // 密码   必填
		"identity":              // 身份证号
		"institution":           // 用户所属机构   必填
	}
```

#### 返回结果

```
    如传递参数错误, 或者操作失败, 则会抛出 exception, exception 错误码、错误信息如下
    {
        1001: "异常参数传入",
    }

    $key (返回激活链接中的key)     string        表示用户注册成功

```

### 更新用户信息

#### 调用函数

`YiQiKong/User/UpdateInfo(object data)`

#### 参数列表

```
	data    object    具体信息, 结构如下:

	{
		"email":          // 邮箱
		"name":           // 姓名
		"gender":         // 性别
		"phone":          // 电话
		"identity":       // 身份证号
		"residence":      // 用户居住地
		"institution":    // 用户所属机构
	}
```

#### 返回结果

```
    如果不能根据邮箱获取到用户 则会抛出 exception, exception 错误码、错误信息如下
    {
        1001: "异常参数传入",
    }

    true     bool        表示用户信息更新成功
    false    bool        表示用户信息更新失败

```

### 新注册用户激活

#### 调用函数

`YiQiKong/User/Activation(string key)`

#### 参数列表

```
    key    string    激活链接中的字符串

```

#### 返回结果

```
    '1'    string    表示激活成功
    '2'    string    激活链接超时需要重新发送激活链接
    '3'    string    账户已经被激活, 无需再次激活
```

### 重新生成激活链接中的key值

#### 调用函数

`YiQiKong/User/ReSend(string key)`

#### 参数列表

```
    key    string    激活链接中的字符串

```

#### 返回结果

```
    key    string    新生成的key值
    false    bool    重新生成时发生错误
```












### 用户登录

#### 调用函数

``

#### 参数列表

```
	
```

#### 返回结果

```
	
```

### 获取用户信息

#### 调用函数

```

```

#### 返回结果

```

```

